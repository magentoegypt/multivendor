<?php

namespace Vnecoms\VendorsCms\Model\Rule\Condition;

class Product extends \Magento\CatalogWidget\Model\Rule\Condition\Product
{
    /**
     * @var \Vnecoms\Vendors\Helper\Data
     */
    protected $_helper;

    /**
     * @var array
     */
    protected $specialAttributesProduct;

    /**
     * Product constructor.
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\Backend\Helper\Data $backendData
     * @param \Magento\Eav\Model\Config $config
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Catalog\Model\ResourceModel\Product $productResource
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection $attrSetCollection
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Vnecoms\Vendors\Helper\Data $helper
     * @param array $data
     * @param array $specialAttributesProduct
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\Backend\Helper\Data $backendData,
        \Magento\Eav\Model\Config $config,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection $attrSetCollection,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Vnecoms\Vendors\Helper\Data $helper,
        array $data = [],
        array $specialAttributesProduct = []
    ) {
        $this->_helper = $helper;
        $this->specialAttributesProduct = $specialAttributesProduct;
        parent::__construct($context, $backendData, $config, $productFactory, $productRepository, $productResource, $attrSetCollection, $localeFormat, $storeManager, $data);
    }

    /**
     * Retrieve after element HTML.
     *
     * @return string
     */
    public function getValueAfterElementHtml()
    {
        $html = '';

        switch ($this->getAttribute()) {
            case 'sku':
            case 'ves_category_ids':
            case 'category_ids':
                $image = $this->_assetRepo->getUrl('Vnecoms_VendorsCms::images/rule_chooser_trigger.gif');
                break;
        }

        if (!empty($image)) {
            $html = '<a href="javascript:void(0)" class="rule-chooser-trigger"><img src="'.
                $image.
                '" alt="" class="v-middle rule-chooser-trigger" title="'.
                __(
                    'Open Chooser'
                ).'" /></a>';
        }

        return $html;
    }

    /**
     * Retrieve value element chooser URL.
     *
     * @return string
     */
    public function getValueElementChooserUrl()
    {
        $url = false;
        switch ($this->getAttribute()) {
            case 'sku':
            case 'ves_category_ids':
            case 'category_ids':
                $url = 'cms/rule_product/chooser/attribute/'.$this->getAttribute();
                if ($this->getJsFormObject()) {
                    $url .= '/form/'.$this->getJsFormObject();
                }
                break;
            default:
                break;
        }

        return $url !== false ? $this->_helper->getUrl($url) : '';
    }

    /**
     * @inheritdoc
     *
     * @param array &$attributes
     * @return void
     */
    protected function _addSpecialAttributes(array &$attributes)
    {
        parent::_addSpecialAttributes($attributes);
        if ($this->specialAttributesProduct) {
            foreach ($this->specialAttributesProduct as $attribute) {
                $attributes[$attribute["code"]] = $attribute["name"];
            }
        }
    }

    /**
     * Retrieve Explicit Apply
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getExplicitApply()
    {
        switch ($this->getAttribute()) {
            case 'sku':
            case 'ves_category_ids':
            case 'category_ids':
                return true;
            default:
                break;
        }
        if (is_object($this->getAttributeObject())) {
            switch ($this->getAttributeObject()->getFrontendInput()) {
                case 'date':
                    return true;
                default:
                    break;
            }
        }
        return false;
    }
}
