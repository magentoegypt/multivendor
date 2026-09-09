<?php
declare(strict_types=1);

namespace MagentoEgypt\CheckoutExtend\Plugin\Checkout\CustomerData;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Checkout\CustomerData\AbstractItem;
use Magento\Checkout\Helper\Data as CheckoutHelper;
use Magento\Framework\App\ObjectManager;
use Magento\Quote\Model\Quote\Item;

/**
 * Adds the struck compare-at price AND the low-stock warning to each minicart
 * line.
 *
 * The cart page card shows the pre-discount price beside the charged one, and
 * the minicart should read the same. It cannot be done in a template there: the
 * minicart is rendered by Knockout from the `cart` customer-data section, so the
 * only figures it can show are the ones this array carries.
 *
 * Declared against AbstractItem rather than DefaultItem so configurable, bundle
 * and grouped lines are covered too — DI config is inherited by subclasses, and
 * each product type ships its own item renderer.
 *
 * Both prices are tax-exclusive, and this store displays tax-exclusive prices
 * (tax/display/type unset, so Magento's default of 1). If that setting ever
 * changes, this needs the tax-inclusive pair instead or the struck figure will
 * read low beside the one next to it.
 *
 * THE LOW-STOCK BADGE LIVES HERE RATHER THAN IN A CLASS OF ITS OWN, and that is
 * a deployment constraint, not a design preference. This install runs production
 * mode with compiled DI; a brand-new plugin class would have no entry in
 * generated/metadata, and `setup:di:compile` on a two-core box means taking the
 * storefront down while it runs. This class is already compiled and already
 * plugged into every mini-cart line, so extending it costs nothing. If a compile
 * ever happens, splitting the two concerns is a five-minute job.
 */
class ItemComparePrice
{
    /**
     * Below this many units left, the line is flagged. Five is the figure most
     * storefronts use and the one the ticket's mock-up implies with "Only 2
     * left"; it is deliberately not a config field, because a config field on
     * this install would be one more thing nobody sets.
     */
    private const LOW_STOCK_AT = 5;

    /**
     * @var CheckoutHelper
     */
    private $checkoutHelper;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /*
     * The second argument is optional for the same reason it is optional on
     * MagentoEgypt\CheckoutExtend\ViewModel\FreeShipping: this class IS in the
     * compiled argument map, with one argument, from the last di:compile. Making
     * the new one required would have the compiled factory construct it with one
     * argument and fail. Optional-with-ObjectManager-fallback is Magento core's
     * own idiom for adding a constructor dependency to an already-shipped class.
     */
    public function __construct(
        CheckoutHelper $checkoutHelper,
        ?StockRegistryInterface $stockRegistry = null
    ) {
        $this->checkoutHelper = $checkoutHelper;
        $this->stockRegistry = $stockRegistry ?: ObjectManager::getInstance()->get(StockRegistryInterface::class);
    }

    /**
     * @param  AbstractItem $subject
     * @param  array        $result
     * @param  Item         $item
     * @return array
     */
    public function afterGetItemData(AbstractItem $subject, $result, Item $item)
    {
        if (!is_array($result)) {
            return $result;
        }

        $product = $item->getProduct();

        if (!$product) {
            return $result;
        }

        /*
         * `price` reaches the quote item's product through this module's
         * catalog_attributes.xml — quote items load a restricted attribute set,
         * and without that declaration this is silently null on every line.
         */
        $base  = (float) $product->getData('price');
        $final = (float) $item->getCalculationPrice();

        if ($base > 0.0 && $final > 0.0 && $base - $final > 0.0001) {
            $result['product_price_old'] = $this->checkoutHelper->formatPrice($base);
        }

        $left = $this->stockLeft($item);
        if ($left !== null) {
            $result['hm_stock_left'] = $left;
        }

        return $result;
    }

    /**
     * Units left for this line, or null when there is nothing honest to say.
     *
     * Null — not zero, not a badge — whenever the answer would be misleading:
     * stock is not managed for the product, the item is a composite whose own
     * stock record means nothing (a configurable's stock lives on the child it
     * resolves to), backorders are allowed so "only 2 left" is not a limit at
     * all, or the figure is comfortably high.
     *
     * Wrapped, because a stock record that cannot be read must cost the shopper
     * a badge and not the whole mini-cart.
     */
    private function stockLeft(Item $item): ?int
    {
        try {
            $product = $item->getProduct();
            if (!$product || !$product->getId()) {
                return null;
            }

            //  The line's own simple product where there is one — a
            //  configurable's parent record carries no meaningful quantity.
            $child = $item->getChildren() ? reset($item->getChildren()) : null;
            $stockProductId = $child && $child->getProduct() && $child->getProduct()->getId()
                ? (int) $child->getProduct()->getId()
                : (int) $product->getId();

            $stockItem = $this->stockRegistry->getStockItem(
                $stockProductId,
                (int) $item->getStore()->getWebsiteId()
            );

            if (!$stockItem || !$stockItem->getManageStock() || $stockItem->getBackorders()) {
                return null;
            }

            $qty = (int) $stockItem->getQty();
            if ($qty <= 0 || $qty > self::LOW_STOCK_AT) {
                return null;
            }

            return $qty;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
