<?php
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Plugin\Model\Layer;

/**
 * The prototype's first filter block is headed "Price Range"; Magento's is
 * headed with the price attribute's store label, "Price".
 *
 * Renamed here rather than through i18n because "Price" is one of the most
 * reused strings on the storefront — translating it would rename the PDP, the
 * cart, the order summary and the admin-facing labels along with this block.
 *
 * @see \Mageplaza\LayeredNavigation\Model\Layer\Filter\Price
 */
class PriceFilterNamePlugin
{
    /**
     * @param  object $subject
     * @param  mixed  $result
     * @return \Magento\Framework\Phrase
     */
    public function afterGetName($subject, $result)
    {
        return __('Price Range');
    }
}
