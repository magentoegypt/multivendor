<?php
namespace Vnecoms\VendorsPageBuilder\Plugin\Product;

class AbstractProduct
{
    /**
     * Vendor helper
     * @var \Vnecoms\Vendors\Model\Url
     */
    protected $vendorUrl;

    /**
     * AbstractProduct constructor.
     * @param \Vnecoms\Vendors\Model\Url $vendorUrl
     */
    public function __construct(
        \Vnecoms\Vendors\Model\Url $vendorUrl
    ) {
        $this->vendorUrl = $vendorUrl;
    }

    /**
     * @param \Magento\CatalogWidget\Model\Rule\Condition\Product $subject
     * @param \Closure $proceed
     * @return string
     */
    public function aroundGetValueElementChooserUrl(
        \Magento\CatalogWidget\Model\Rule\Condition\Product $subject,
        \Closure $proceed
    ) {
        $url = false;
        switch ($subject->getAttribute()) {
            case 'sku':
            case 'category_ids':
                $url = 'pagebuilder/promo_widget/chooser/attribute/' . $subject->getAttribute();
                if ($subject->getJsFormObject()) {
                    $url .= '/form/' . $subject->getJsFormObject();
                }
                break;
            default:
                break;
        }
        return $url !== false ? $this->vendorUrl->getUrl($url) : '';
    }
}
