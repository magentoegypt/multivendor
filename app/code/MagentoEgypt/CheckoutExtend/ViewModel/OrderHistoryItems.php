<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\CheckoutExtend\ViewModel;

use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use MagentoEgypt\HomeSections\ViewModel\VendorNames;
use Psr\Log\LoggerInterface;

/**
 * Line items for the order-history cards.
 *
 * The Figma account screen does not list orders as table rows — it draws each
 * order as a card with its products inside it: thumbnail, name, seller, price.
 * Core's history template has none of that; it has four scalars and a link.
 *
 * WHY EVERYTHING IS BATCHED
 * -------------------------
 * `sales.order.history` is declared `cacheable="false"`, so there is no block
 * cache to absorb a slow render — whatever this costs, it costs on every page
 * view. A naive `$order->getAllVisibleItems()` inside the template's loop is one
 * query per order, and this store already has customers with 21 orders.
 *
 * MEASURED COST, on the worst account in this database — 21 orders, 32 lines,
 * 14 distinct products: 18 queries, and flat in the number of ORDERS. The
 * breakdown, because the headline number is easy to misread:
 *
 *     1   sales_order_item, every order on the page in one select
 *     ~11 the product collection — Magento's EAV loader queries per backend
 *         TYPE, not per attribute, so trimming the select does nothing.
 *         Measured both ways; the select is sized for correctness instead.
 *     ~6  url rewrites that addUrlRewrite() did not cover, because those
 *         products have no rewrite row and getProductUrl() falls back per
 *         product. Kept deliberately: a wrong product link is worse than a
 *         query, and the alternative is rebuilding the URL from url_key and
 *         hoping no custom rewrite exists.
 *
 * What matters is that none of those terms grows with the order count — the
 * same page at 5 orders costs the same as at 21.
 *
 * The vendor name reuses HomeSections' VendorNames, which reads the 25-row
 * vendor table once per request and is usually already warm.
 */
class OrderHistoryItems implements ArgumentInterface
{
    /** @var array<int, array<int, array<string, mixed>>>|null orderId => items */
    private ?array $byOrder = null;

    public function __construct(
        private readonly OrderItemCollectionFactory $itemCollectionFactory,
        private readonly ProductCollectionFactory $productCollectionFactory,
        private readonly ImageHelper $imageHelper,
        private readonly VendorNames $vendorNames,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Resolve every line on the page up front. Call once, from the template,
     * with the collection the block is about to render — the cost is flat in
     * the number of orders, which is the whole point (see the class note).
     *
     * @param iterable<OrderInterface> $orders
     */
    public function preload(iterable $orders): void
    {
        if ($this->byOrder !== null) {
            return;
        }

        $this->byOrder = [];

        $orderIds = [];
        foreach ($orders as $order) {
            $orderIds[] = (int) $order->getId();
        }
        if (!$orderIds) {
            return;
        }

        try {
            $items = $this->itemCollectionFactory->create()
                ->addFieldToFilter('order_id', ['in' => $orderIds])
                //  Configurable and bundle children carry a parent; rendering
                //  both would show the same product twice at two prices.
                ->addFieldToFilter('parent_item_id', ['null' => true]);

            $productIds = [];
            foreach ($items as $item) {
                $productIds[(int) $item->getProductId()] = true;
            }

            $products = [];
            if ($productIds) {
                $collection = $this->productCollectionFactory->create()
                    /*
                     * `small_image` IS load-bearing even though nothing here
                     * reads it by name: the `category_page_grid` image id used
                     * below resolves its type from blank's view.xml, and that
                     * type is small_image. This theme's view.xml overrides only
                     * the width and height. Dropping it from the select made
                     * every thumbnail fall back to the Magento placeholder —
                     * which looks exactly like a broken-image-path problem and
                     * is not one.
                     *
                     * `name` is deliberately absent: the order LINE carries the
                     * name as it was when the order was placed, which is the
                     * one to show, not today's catalogue value.
                     */
                    ->addAttributeToSelect(['thumbnail', 'small_image', 'url_key', 'vendor_id'])
                    ->addIdFilter(array_keys($productIds));

                /*
                 * `addUrlRewrite()` joins the rewrite table into the collection
                 * load. It is worth having — it took the page from 24 queries to
                 * 18 — but it does NOT eliminate the per-product lookup: products
                 * with no rewrite row still fall back inside getProductUrl(), six
                 * of fourteen on this data. Measured, not assumed.
                 */
                $collection->addUrlRewrite();

                foreach ($collection as $product) {
                    $products[(int) $product->getId()] = $product;
                }
            }

            foreach ($items as $item) {
                $orderId = (int) $item->getOrderId();
                $product = $products[(int) $item->getProductId()] ?? null;

                $this->byOrder[$orderId][] = [
                    'name'   => (string) $item->getName(),
                    'sku'    => (string) $item->getSku(),
                    'qty'    => (int) $item->getQtyOrdered(),
                    'price'  => (float) $item->getPrice(),
                    /*
                     * A deleted product still has an order line — the order is a
                     * historical record, not a view of the catalogue — so both
                     * the image and the link have to tolerate a missing product.
                     */
                    'thumb'  => $product ? $this->thumbnailUrl($product) : null,
                    'url'    => $product ? $product->getProductUrl() : null,
                    'vendor' => $product ? $this->vendorNames->getName($product->getData('vendor_id')) : null,
                ];
            }
        } catch (\Throwable $e) {
            /*
             * The card must degrade to its header and actions rather than take
             * the account page down: this is an enhancement to a list that is
             * perfectly usable without product thumbnails.
             */
            $this->logger->warning('CheckoutExtend: order history items unavailable: ' . $e->getMessage());
            $this->byOrder = [];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getItems(OrderInterface $order): array
    {
        return $this->byOrder[(int) $order->getId()] ?? [];
    }

    /**
     * Modifier for the status pill, so the card reads at a glance the way the
     * reference's Delivered / In Transit / Processing pills do.
     *
     * Keyed on the STATUS CODE, not the label: labels are admin-editable and
     * translated. Unknown codes — and this install grows custom ones — fall
     * back to a neutral pill rather than being mis-coloured.
     */
    public function getStatusTint(?string $status): string
    {
        switch ((string) $status) {
            case 'complete':
                return 'success';
            case 'processing':
            case 'pending':
            case 'pending_payment':
            case 'payment_review':
                return 'warning';
            case 'canceled':
            case 'closed':
                return 'danger';
            case 'holded':
            case 'fraud':
                return 'info';
            default:
                return 'neutral';
        }
    }

    private function thumbnailUrl($product): ?string
    {
        try {
            return $this->imageHelper->init($product, 'category_page_grid')->getUrl();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
