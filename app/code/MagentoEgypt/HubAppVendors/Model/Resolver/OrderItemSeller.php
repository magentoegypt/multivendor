<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Resolver;

use Magento\Framework\DataObject;

/**
 * OrderItemInterface.hm_seller — the seller of each order line (sales_order_item.vendor_id).
 *
 * The order line keeps the seller it was sold by (copied from the quote item
 * when the order was placed), so a later change of the product's owner does not
 * rewrite order history. 0 is Hub Market. One batch for every line of a
 * response; orders are read with the customer's token (POST, not cached).
 */
class OrderItemSeller extends AbstractSellerResolver
{
    /**
     * @inheritDoc
     */
    protected function vendorIds(array $requests): array
    {
        $out = [];
        foreach ($requests as $key => $request) {
            $value = (array) ($request->getValue() ?? []);
            $item = $value['model'] ?? null;
            $out[$key] = $item instanceof DataObject
                ? (int) $item->getData('vendor_id')
                : (array_key_exists('vendor_id', $value) ? (int) $value['vendor_id'] : null);
        }

        return $out;
    }
}
