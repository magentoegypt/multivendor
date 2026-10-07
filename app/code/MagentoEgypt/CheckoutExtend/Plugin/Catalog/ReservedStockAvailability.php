<?php
/**
 * A product whose last units are reserved by open orders is out of stock on the storefront.
 *
 * WHAT QA REPORTED ([CL036-TC95], 14zb93nw8g1)
 * --------------------------------------------
 * fresh12 "Fresh 5-Drawer Freezer": admin Quantity 1, Salable Quantity 0. The
 * storefront showed it In Stock with an Add to Cart button, and Add to Cart
 * answered "Not enough items for sale".
 *
 * WHY
 * ---
 * Two different questions were being asked. Product::isSalable() / isAvailable(),
 * which every Hub Market template uses for the stock label and the button, read
 * the MSI stock index, and the index deliberately ignores reservations: it knew
 * of 1 unit on the shelf. Add to Cart asks IsProductSalableForRequestedQty, which
 * subtracts reservations, and that unit is promised to order #000000011
 * (invoiced, never shipped) — so salable 0. The reservation is genuine;
 * inventory:reservation:list-inconsistencies does not list it.
 *
 * WHAT THIS DOES
 * --------------
 * Storefront only. After the index has said "salable", a stock-holding product
 * (simple, virtual, downloadable) whose SKU carries a net negative reservation is
 * re-asked with the exact check Add to Cart uses, for one unit. Products with no
 * outstanding reservation — nearly all of them — cost one shared query per request
 * and nothing else. Composite products keep their own logic (their children are
 * checked when a child is chosen).
 *
 * Fails open: if the check itself errors, the index's answer stands.
 */
declare(strict_types=1);

namespace MagentoEgypt\CheckoutExtend\Plugin\Catalog;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\ResourceConnection;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\IsProductSalableForRequestedQtyInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class ReservedStockAvailability
{
    /** Types that hold their own stock; composites are decided by their children. */
    private const STOCK_TYPES = ['simple', 'virtual', 'downloadable'];

    /** @var array<int, array<string, true>> stock id => SKUs with a net negative reservation */
    private array $reservedSkus = [];

    /** @var array<string, bool> "stockId|sku" => salable for one unit */
    private array $verdicts = [];

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly StockResolverInterface $stockResolver,
        private readonly StoreManagerInterface $storeManager,
        private readonly IsProductSalableForRequestedQtyInterface $isSalableForQty,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param Product $product
     * @param bool $result
     * @return bool
     */
    public function afterIsSalable(Product $product, $result)
    {
        return $result ? $this->stillSalable($product) : $result;
    }

    /**
     * @param Product $product
     * @param bool $result
     * @return bool
     */
    public function afterIsAvailable(Product $product, $result)
    {
        return $result ? $this->stillSalable($product) : $result;
    }

    private function stillSalable(Product $product): bool
    {
        if (!in_array((string) $product->getTypeId(), self::STOCK_TYPES, true)) {
            return true;
        }

        $sku = (string) $product->getSku();
        if ($sku === '') {
            return true;
        }

        try {
            $stockId = $this->stockId();
            if (!isset($this->reservedSkus($stockId)[$sku])) {
                return true;
            }

            $key = $stockId . '|' . $sku;
            if (!array_key_exists($key, $this->verdicts)) {
                $this->verdicts[$key] = $this->isSalableForQty->execute($sku, $stockId, 1)->isSalable();
            }

            return $this->verdicts[$key];
        } catch (\Throwable $e) {
            $this->logger->warning('ReservedStockAvailability: ' . $sku . ': ' . $e->getMessage());

            return true;
        }
    }

    private function stockId(): int
    {
        $websiteCode = (string) $this->storeManager->getWebsite()->getCode();

        return (int) $this->stockResolver
            ->execute(SalesChannelInterface::TYPE_WEBSITE, $websiteCode)
            ->getStockId();
    }

    /**
     * @return array<string, true>
     */
    private function reservedSkus(int $stockId): array
    {
        if (!isset($this->reservedSkus[$stockId])) {
            $connection = $this->resource->getConnection();
            $skus = $connection->fetchCol(
                $connection->select()
                    ->from($this->resource->getTableName('inventory_reservation'), ['sku'])
                    ->where('stock_id = ?', $stockId)
                    ->group('sku')
                    ->having('SUM(quantity) < 0')
            );
            $this->reservedSkus[$stockId] = array_fill_keys(array_map('strval', $skus), true);
        }

        return $this->reservedSkus[$stockId];
    }
}
