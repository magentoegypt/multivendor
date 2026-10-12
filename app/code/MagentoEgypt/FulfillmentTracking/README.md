# MagentoEgypt_FulfillmentTracking

Customer-facing delivery tracking for `MagentoEgypt_Fulfillment` (CL036-DEV17). Read-only: sellers, the
marketplace team and the fleet change fulfillment state through `MagentoEgypt_Fulfillment` (seller order
page, admin workspace, fleet REST). This module shows the result to the customer, the same way in two places:

- **Customer app**: `CustomerOrder.hm_fulfillment` (GraphQL), advertised as capability `fulfillment` in
  `hmAppConfig.capabilities`.
- **Website**: a "Delivery progress" section on the customer order page (`sales/order/view`) and the guest
  order page (`sales/guest/view`).

Both come from `Model\DeliveryProjection` and `Model\Labels`, so a status never reads one way in the app and
another on the website.

## What a customer sees

An order's deliveries are the groups of `Fulfillment\Model\Dispatch::describe()`:

- orders placed before fulfillment planning: one delivery per seller (`route: direct`);
- planned orders: one per checkout leg — `direct` (seller to customer), `to_hub` (seller to the Hub Market
  warehouse), `from_hub` (warehouse to customer).

Each delivery has a customer status, its route, the seller, who delivers it (`handled_by`), the courier and
tracking number when known, an expected delivery date when the team set one, the order lines it carries
(`item_uids`, the same ids as `CustomerOrder.items[].id`) and a timeline.

### Status codes

| code | EN | when |
|---|---|---|
| `ordered` | Order placed | order new / pending payment / payment review |
| `preparing` | Preparing | accepted, nothing moved yet |
| `ready_for_pickup` | Ready for courier pickup | seller marked it ready |
| `to_hub` | On the way to our warehouse | dispatched on a `to_hub` leg |
| `at_hub` | At our warehouse | received at the warehouse |
| `on_the_way` | On the way to you | dispatched / shipped (reported or confirmed) towards the customer |
| `shipped` | Shipped | untracked delivery whose lines have a Magento shipment |
| `delivered` | Delivered | delivered (reported or confirmed) |
| `completed` | Completed | untracked delivery whose (vendor) order is complete |
| `delayed` | Delivery delayed | the team recorded an issue (the internal note is never shown) |
| `on_hold` | On hold | order or vendor order on hold |
| `canceled` | Canceled | order or vendor order canceled |
| `refunded` | Refunded | order or vendor order closed |

`tracked: false` means nobody has recorded fulfillment progress for that delivery yet (every order placed
before the module, until its team does): the status then follows the vendor order's state, else the order's,
and the Magento shipments of its lines — what the website's order page already shows. Nothing is guessed.

Overall `status_code`: an order-level state (new, canceled, closed, holded) decides alone; otherwise
`delayed` or `on_hold` on any delivery wins; through the warehouse the `from_hub` leg decides once it moves,
until then the slowest `to_hub` leg; otherwise the slowest delivery. Canceled or refunded parts of an order
with other live parts are left out.

### Never exposed

Source codes and warehouse addresses, rates and costs, stock, internal notes, staff actions, group versions
and internal group ids (deliveries are `delivery-1`, `delivery-2`, …). The delivery address is already on the
order.

## App integration

1. Read `hmAppConfig { capabilities }`. Ask for `hm_fulfillment` only when it lists `fulfillment`.
2. Query it on the order the app already loads; access is the core query's (`customer.orders` with the
   customer token, `guestOrder` / `guestOrderByToken` for guests). Nothing is fetched by id.

```graphql
query OrderDelivery($number: String!) {
  customer {
    orders(filter: { number: { eq: $number } }) {
      items {
        number
        hm_fulfillment {
          status_code
          status_label
          deliveries {
            id route route_label handled_by
            status_code status_label tracked
            seller { name logo_url is_marketplace }
            courier { type label name tracking_number tracking_url }
            expected_delivery updated_at
            item_uids
            timeline { status_code status_label at }
          }
        }
      }
    }
  }
}
```

Send the `Store` header (`en` / `ar`) as for every other app query: labels come back in that store's
language. Times are ISO-8601 UTC. `tracking_url` is null for carriers without a public tracking page (show
`tracking_number` to copy).

`hm_fulfillment` is null only when the order's progress could not be read (logged as
`FulfillmentTracking:` in `var/log/system.log`); an order list never fails because of it.

## Not changed

The module's REST `GET /V1/hubfulfillment/order/:orderId` and `/order-number/:number` (Fulfillment's
`CustomerApi`) keep their contract: the checkout plan as a JSON string, `legacy_order` for unplanned orders.
New app work should use the GraphQL field, which covers every order.

## Deployment

No tables, no `setup_version`. `bin/magento module:enable MagentoEgypt_FulfillmentTracking`, then (production)
`setup:di:compile`, `php8.4-fpm` restart and `cache:clean config layout translate`.

Tests: `Test/Unit` (status mapping, capability wiring).
