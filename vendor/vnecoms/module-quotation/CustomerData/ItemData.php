<?php

namespace Vnecoms\Quotation\CustomerData;

use \Vnecoms\Quotation\Model\Item;

class ItemData
{
    /**
     * @var Item
     */
    protected $item;

    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $imageHelper;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Catalog\Helper\Product\ConfigurationPool
     */
    protected $configurationPool;

    /**
     * @var \Vnecoms\Quotation\Helper\Data
     */
    protected $helper;

    public function __construct(
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Catalog\Helper\Product\ConfigurationPool $configurationPool,
        \Vnecoms\Quotation\Helper\Data $helper
    ) {
        $this->configurationPool = $configurationPool;
        $this->imageHelper = $imageHelper;
        $this->urlBuilder = $urlBuilder;
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function getItemData(Item $item)
    {
        $this->item = $item;
        return \array_merge(
            ['product_type' => $item->getProductType()],
            $this->doGetItemData()
        );
    }

    /**
     * Item data quote added
     *
     * @return array
     */
    public function doGetItemData()
    {
        $imageHelper = $this->imageHelper->init($this->getProduct(), 'mini_cart_product_thumbnail');
        $itemPrice = $this->getItemStorePrice($this->item);

        return [
            'options' => $this->getOptionList(),
            'qty' => $this->item->getQty() * 1,
            'item_id' => $this->item->getId(),
            'configure_url' => $this->getConfigureUrl(),
            'is_visible_in_site_visibility' => $this->item->getProduct()->isVisibleInSiteVisibility(),
            'product_name' => $this->item->getProduct()->getName(),
            'product_sku' => $this->item->getProduct()->getSku(),
            'product_url' => $this->getProductUrl(),
            'product_price' => $this->item->getStore()->getCurrentCurrency()->format($itemPrice),
            'product_price_value' => $itemPrice,
            'product_image' => [
                'src' => $imageHelper->getUrl(),
                'alt' => $imageHelper->getLabel(),
                'width' => $imageHelper->getWidth(),
                'height' => $imageHelper->getHeight(),
            ]
        ];
    }

    /**
     * Get item store price
     * 
     * @param \Vnecoms\Quotation\Model\Item $item
     */
    public function getItemStorePrice(\Vnecoms\Quotation\Model\Item $item){
        $store = $item->getStore();
        return $store->getBaseCurrency()->convert($item->getBasePrice(), $store->getCurrentCurrency()->getCode());
    }
    
    /**
     * Retrieve configure quote item url
     *
     * @param void
     * @return string
     */
    public function getConfigureUrl()
    {
        return $this->urlBuilder->getUrl(
            'quotation/quote/configure',
            ['id' => $this->item->getId(), 'product_id' => $this->item->getProduct()->getId()]
        );
    }

    /**
     * @return array
     */
    public function getOptionList()
    {
        return $this->configurationPool->getByProductType('quotation_'.$this->item->getProductType())->getOptions($this->item);
    }

    /**
     * Retrieve product
     *
     * @return \Magento\Catalog\Model\Product
     * @codeCoverageIgnore
     */
    protected function getProduct()
    {
        return $this->item->getProduct();
    }

    /**
     * Retrieve URL to item Product
     *
     * @return string
     */
    protected function getProductUrl()
    {
        $product = $this->item->getProduct();
        return $product->getUrlModel()->getUrl($product);
    }
}
