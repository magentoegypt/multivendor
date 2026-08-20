<?php
declare(strict_types=1);

namespace MagentoEgypt\CheckoutExtend\Plugin\Checkout\CustomerData;

use Magento\Checkout\CustomerData\AbstractItem;
use Magento\Checkout\Helper\Data as CheckoutHelper;
use Magento\Quote\Model\Quote\Item;

/**
 * Adds the struck compare-at price to each minicart line.
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
 */
class ItemComparePrice
{
    /**
     * @var CheckoutHelper
     */
    private $checkoutHelper;

    public function __construct(CheckoutHelper $checkoutHelper)
    {
        $this->checkoutHelper = $checkoutHelper;
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

        return $result;
    }
}
