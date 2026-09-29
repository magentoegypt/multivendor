<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Model\Rma;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Serialize\Serializer\Json;
use MagentoEgypt\HubApp\Api\MediaUrlInterface;
use MagentoEgypt\HubApp\Api\SellerSummaryProviderInterface;
use MagentoEgypt\HubApp\Api\StorefrontEmulationInterface;
use Psr\Log\LoggerInterface;

/**
 * Order lines (sales_order_item) for returns: the quantities the rules need and what the app shows
 * (SKU, name, chosen options, thumbnail, seller). Batched: one query for the lines, one product
 * collection for the thumbnails and one seller-summary call, whatever the number of lines.
 */
class OrderLineReader
{
    /** A list-row thumbnail the storefront theme defines (Magento/blank view.xml, small_image). */
    private const IMAGE_ID = 'cart_page_product_thumbnail';

    private const COLUMNS = [
        'item_id', 'order_id', 'parent_item_id', 'product_id', 'product_type', 'sku', 'name', 'product_options',
        'vendor_id', 'qty_ordered', 'qty_shipped', 'qty_invoiced', 'qty_refunded', 'row_total_incl_tax',
        'discount_amount',
    ];

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly Json $json,
        private readonly ProductCollectionFactory $productCollectionFactory,
        private readonly MediaUrlInterface $mediaUrl,
        private readonly StorefrontEmulationInterface $emulation,
        private readonly SellerSummaryProviderInterface $sellers,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Every line of these orders, child lines included: ReturnableLines picks the ones a customer can
     * return (a bundle's child lines stand in for the bundle line, as on the website).
     *
     * @param int[] $orderIds
     * @return array<int, array<string, mixed>> item id => sales_order_item row, by item id
     */
    public function linesOfOrders(array $orderIds): array
    {
        $orderIds = $this->ids($orderIds);
        if (!$orderIds) {
            return [];
        }
        $connection = $this->resource->getConnection();

        return $this->keyed($connection->fetchAll(
            $connection->select()
                ->from($this->resource->getTableName('sales_order_item'), self::COLUMNS)
                ->where('order_id IN (?)', $orderIds)
                ->order('item_id ASC')
        ));
    }

    /**
     * @param int[] $itemIds
     * @return array<int, array<string, mixed>> item id => sales_order_item row
     */
    public function linesById(array $itemIds): array
    {
        $itemIds = $this->ids($itemIds);
        if (!$itemIds) {
            return [];
        }
        $connection = $this->resource->getConnection();

        return $this->keyed($connection->fetchAll(
            $connection->select()
                ->from($this->resource->getTableName('sales_order_item'), self::COLUMNS)
                ->where('item_id IN (?)', $itemIds)
                ->order('item_id ASC')
        ));
    }

    /**
     * What the app shows for each line.
     *
     * @param array<int, array<string, mixed>> $lines item id => sales_order_item row
     * @return array<int, array{sku: string, name: string, options: array<int, array{label: string, value: string}>,
     *     image_url: ?string, seller: ?array<string, mixed>}>
     */
    public function present(array $lines, int $storeId): array
    {
        if (!$lines) {
            return [];
        }
        $images = $this->thumbnails(array_column($lines, 'product_id'), $storeId);
        $sellers = $this->sellerSummaries(array_column($lines, 'vendor_id'), $storeId);

        $out = [];
        foreach ($lines as $itemId => $line) {
            $options = $this->productOptions($line['product_options'] ?? null);
            $simpleSku = isset($options['simple_sku']) && is_string($options['simple_sku']) ? $options['simple_sku'] : '';
            $out[(int) $itemId] = [
                'sku' => $simpleSku !== '' ? $simpleSku : (string) $line['sku'],
                'name' => (string) $line['name'],
                'options' => $this->optionRows($options),
                'image_url' => $images[(int) $line['product_id']] ?? null,
                'seller' => $sellers[(int) $line['vendor_id']] ?? null,
            ];
        }

        return $out;
    }

    /**
     * The website's refund base for a line: (row total incl. tax - discount) / qty ordered, per unit
     * (Vnecoms\VendorsRMA\Observer\RequestValidateItem, Model\Request::saveAmountRefundObject).
     *
     * @param array<string, mixed> $line
     */
    public function refundPerUnit(array $line): float
    {
        $qtyOrdered = (float) $line['qty_ordered'];
        if ($qtyOrdered <= 0) {
            return 0.0;
        }

        return ((float) $line['row_total_incl_tax'] - (float) $line['discount_amount']) / $qtyOrdered;
    }

    /**
     * Seller summaries keyed by vendor entity id (0 = Hub Market); absent for unapproved sellers.
     *
     * @param array<int, int|string|null> $vendorIds
     * @return array<int, array<string, mixed>>
     */
    public function sellerSummaries(array $vendorIds, int $storeId): array
    {
        $vendorIds = array_values(array_unique(array_map('intval', $vendorIds)));
        if (!$vendorIds) {
            return [];
        }
        try {
            return $this->sellers->getByVendorIds($vendorIds, $storeId);
        } catch (\Throwable $e) {
            $this->logger->warning('HubAppReturns: seller summaries unavailable: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * @param array<int, int|string|null> $productIds
     * @return array<int, string> product id => absolute https thumbnail
     */
    private function thumbnails(array $productIds, int $storeId): array
    {
        $productIds = $this->ids($productIds);
        if (!$productIds) {
            return [];
        }
        try {
            $collection = $this->productCollectionFactory->create()
                ->setStoreId($storeId)
                ->addIdFilter($productIds)
                ->addAttributeToSelect(['small_image', 'thumbnail', 'image']);
            $products = $collection->getItems();
        } catch (\Throwable $e) {
            $this->logger->warning('HubAppReturns: thumbnails unavailable: ' . $e->getMessage());

            return [];
        }

        return $this->emulation->run($storeId, function () use ($products, $storeId): array {
            $out = [];
            foreach ($products as $product) {
                $url = $this->mediaUrl->productImage($product, self::IMAGE_ID, $storeId);
                if ($url !== null) {
                    $out[(int) $product->getId()] = $url;
                }
            }

            return $out;
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function productOptions(mixed $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }
        if (!is_string($raw) || $raw === '') {
            return [];
        }
        try {
            $decoded = $this->json->unserialize($raw);
        } catch (\Throwable $e) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * The chosen options as the website's return form lists them (DefaultItems::getItemOptions:
     * custom options, additional options, configurable attributes), plus bundle selections.
     *
     * @param array<string, mixed> $options
     * @return array<int, array{label: string, value: string}>
     */
    private function optionRows(array $options): array
    {
        $rows = [];
        foreach (['options', 'additional_options', 'attributes_info'] as $group) {
            foreach ((array) ($options[$group] ?? []) as $option) {
                if (!is_array($option)) {
                    continue;
                }
                $value = $option['print_value'] ?? $option['value'] ?? '';
                $rows[] = [(string) ($option['label'] ?? ''), $this->plain($value)];
            }
        }
        foreach ((array) ($options['bundle_options'] ?? []) as $bundleOption) {
            if (!is_array($bundleOption)) {
                continue;
            }
            $picked = [];
            foreach ((array) ($bundleOption['value'] ?? []) as $selection) {
                if (!is_array($selection)) {
                    continue;
                }
                $qty = (float) ($selection['qty'] ?? 1);
                $title = $this->plain($selection['title'] ?? '');
                $picked[] = $qty > 1 ? sprintf('%s x %s', rtrim(rtrim(sprintf('%.4F', $qty), '0'), '.'), $title) : $title;
            }
            $rows[] = [(string) ($bundleOption['label'] ?? ''), implode(', ', array_filter($picked, 'strlen'))];
        }

        $out = [];
        foreach ($rows as [$label, $value]) {
            $label = $this->plain($label);
            if ($label !== '' && $value !== '') {
                $out[] = ['label' => $label, 'value' => $value];
            }
        }

        return $out;
    }

    private function plain(mixed $value): string
    {
        if (is_array($value)) {
            $value = implode(', ', array_map(fn ($v): string => $this->plain($v), $value));
        }
        $text = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function keyed(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row['item_id']] = $row;
        }

        return $out;
    }

    /**
     * @param array<int, int|string|null> $ids
     * @return int[]
     */
    private function ids(array $ids): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)));
    }
}
