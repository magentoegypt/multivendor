<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 */
namespace Vnecoms\Quotation\Block\Quotepage\Item;

use Vnecoms\Quotation\Block\Quotepage\Item\Renderer\Actions;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Message\InterpretationStrategyInterface;
use Vnecoms\Quotation\Model\Item;
use Magento\Catalog\Pricing\Price\ConfiguredPriceInterface;

class Renderer extends \Magento\Framework\View\Element\Template implements
    \Magento\Framework\DataObject\IdentityInterface
{
    /**
     * @var \Vnecoms\Quotation\Model\Session
     */
    protected $session;

    /**
     * @var Item
     */
    protected $_item;

    /**
     * @var string
     */
    protected $_productUrl;

    /**
     * Check, whether product URL rendering should be ignored
     *
     * @var bool
     */
    protected $_ignoreProductUrl = false;

    /**
     * Catalog product configuration
     *
     * @var \Magento\Catalog\Helper\Product\Configuration
     */
    protected $_productConfig = null;

    /**
     * @var \Magento\Framework\Url\Helper\Data
     */
    protected $_urlHelper;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Catalog\Block\Product\ImageBuilder
     */
    protected $imageBuilder;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    public $moduleManager;

    /**
     * @var InterpretationStrategyInterface
     */
    private $messageInterpretationStrategy;

    /**
     * Magento string lib
     *
     * @var \Magento\Framework\Stdlib\StringUtils
     */
    protected $string;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Helper\Product\Configuration $productConfig,
        \Vnecoms\Quotation\Model\Session $session,
        \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Module\Manager $moduleManager,
        InterpretationStrategyInterface $messageInterpretationStrategy,
        \Magento\Framework\Stdlib\StringUtils $stringUtils,
        array $data = []
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->imageBuilder = $imageBuilder;
        $this->_urlHelper = $urlHelper;
        $this->_productConfig = $productConfig;
        $this->session = $session;
        $this->messageManager = $messageManager;
        parent::__construct($context, $data);
        $this->_isScopePrivate = true;
        $this->moduleManager = $moduleManager;
        $this->messageInterpretationStrategy = $messageInterpretationStrategy;
        $this->string = $stringUtils;
    }

    /**
     * Set item for render
     *
     * @param Item $item
     * @return $this
     * @codeCoverageIgnore
     */
    public function setItem(Item $item)
    {
        $this->_item = $item;
        return $this;
    }

    /**
     * Get quote item
     *
     * @return Item
     * @codeCoverageIgnore
     */
    public function getItem()
    {
        return $this->_item;
    }

    /**
     * Get item product
     *
     * @return \Magento\Catalog\Model\Product
     * @codeCoverageIgnore
     */
    public function getProduct()
    {
        return $this->getItem()->getProduct();
    }

    /**
     * Identify the product from which thumbnail should be taken.
     *
     * @return \Magento\Catalog\Model\Product
     * @codeCoverageIgnore
     */
    public function getProductForThumbnail()
    {
        return $this->getProduct();
    }

    /**
     * @param string $productUrl
     * @return $this
     * @codeCoverageIgnore
     */
    public function overrideProductUrl($productUrl)
    {
        $this->_productUrl = $productUrl;
        return $this;
    }

    /**
     * Check Product has URL
     *
     * @return bool
     */
    public function hasProductUrl()
    {
        if ($this->_ignoreProductUrl) {
            return false;
        }

        if ($this->_productUrl) {
            return true;
        }

        $product = $this->getProduct();
        $option = $this->getItem()->getOptionByCode('product_type');
        if ($option && !is_null($option->getProduct())) {
            $product = $option->getProduct();
        }

        if (!$product) {
            return false;
        }

        if ($product->isVisibleInSiteVisibility()) {
            return true;
        } else {
            if ($product->hasUrlDataObject()) {
                $data = $product->getUrlDataObject();
                if (in_array($data->getVisibility(), $product->getVisibleInSiteVisibilities())) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Retrieve URL to item Product
     *
     * @return string
     */
    public function getProductUrl()
    {
        if ($this->_productUrl !== null) {
            return $this->_productUrl;
        }

        $product = $this->getProduct();

        return $product->getUrlModel()->getUrl($product);
    }

    /**
     * Get item product name
     *
     * @return string
     */
    public function getProductName()
    {
        if ($this->hasProductName()) {
            return $this->getData('product_name');
        }
        return $this->getProduct()->getName();
    }

    /**
     * Get product customize options
     *
     * @return array
     */
    public function getProductOptions()
    {
        /* @var $helper \Magento\Catalog\Helper\Product\Configuration */
        $helper = $this->_productConfig;
        return $helper->getCustomOptions($this->getItem());
    }

    /**
     * Get list of all otions for product
     *
     * @return array
     * @codeCoverageIgnore
     */
    public function getOptionList()
    {
        return $this->getProductOptions();
    }

    /**
     * Get quote item qty
     *
     * @return float|int
     */
    public function getQty()
    {
        return $this->getItem()->getQty() * 1;
    }
    
    /**
     * Get quote item qty
     *
     * @return float|int
     */
    public function getPrice()
    {
        return $this->getItem()->getPrice() * 1;
    }


    /**
     * Accept option value and return its formatted view
     *
     * @param string|array $optionValue
     * Method works well with these $optionValue format:
     *      1. String
     *      2. Indexed array e.g. array(val1, val2, ...)
     *      3. Associative array, containing additional option info, including option value, e.g.
     *          array
     *          (
     *              [label] => ...,
     *              [value] => ...,
     *              [print_value] => ...,
     *              [option_id] => ...,
     *              [option_type] => ...,
     *              [custom_view] =>...,
     *          )
     *
     * @return array
     */
    public function getFormatedOptionValue($optionValue)
    {
        /* @var $helper \Magento\Catalog\Helper\Product\Configuration */
        $helper = $this->_productConfig;
        $params = [
            'max_length' => 55,
            'cut_replacer' => ' <a href="#" class="dots tooltip toggle" onclick="return false">...</a>'
        ];
        return $helper->getFormattedOptionValue($optionValue, $params);
    }

    /**
     * Check whether Product is visible in site
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function isProductVisible()
    {
        return $this->getProduct()->isVisibleInSiteVisibility();
    }

    /**
     * Return product additional information block
     *
     * @return AbstractBlock
     * @codeCoverageIgnore
     */
    public function getProductAdditionalInformationBlock()
    {
        return $this->getLayout()->getBlock('additional.product.info');
    }


    /**
     * Set ignore product URL rendering
     *
     * @param bool $ignore
     * @return $this
     * @codeCoverageIgnore
     */
    public function setIgnoreProductUrl($ignore = true)
    {
        $this->_ignoreProductUrl = $ignore;
        return $this;
    }

    /**
     * Return identifiers for produced content
     *
     * @return array
     */
    public function getIdentities()
    {
        $identities = [];
        if ($this->getItem()) {
            $identities = $this->getProduct()->getIdentities();
        }
        return $identities;
    }

    /**
     * Get product price formatted with html (final price, special price, mrp price)
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    public function getProductPriceHtml(\Magento\Catalog\Model\Product $product)
    {
        $priceRender = $this->getPriceRender();
        $priceRender->setItem($this->getItem());

        $price = '';
        if ($priceRender) {
            $price = $priceRender->render(
                ConfiguredPriceInterface::CONFIGURED_PRICE_CODE,
                $product,
                [
                    'include_container' => true,
                    'display_minimal_price' => true,
                    'zone' => \Magento\Framework\Pricing\Render::ZONE_ITEM_LIST
                ]
            );
        }

        return $price;
    }

    /**
     * @return \Magento\Framework\Pricing\Render
     * @codeCoverageIgnore
     */
    protected function getPriceRender()
    {
        return $this->getLayout()->getBlock('product.price.render.default');
    }

    /**
     * Convert prices for template
     *
     * @param float $amount
     * @param bool $format
     * @return float
     */
    public function convertPrice($amount, $format = false)
    {
        return $format
            ? $this->priceCurrency->convertAndFormat($amount)
            : $this->priceCurrency->convert($amount);
    }

    /**
     * Return the unit price html
     *
     * @param Item $item
     * @return string
     */
    public function getUnitPriceHtml(Item $item)
    {
        /** @var Renderer $block */
        $block = $this->getLayout()->getBlock('quote.item.price.unit');
        $block->setItem($item);
        return $block->toHtml();
    }

    /**
     * Return row total html
     *
     * @param Item $item
     * @return string
     */
    public function getRowTotalHtml(Item $item)
    {
        /** @var Renderer $block */
        $block = $this->getLayout()->getBlock('quote.item.price.row');
        $block->setItem($item);
        return $block->toHtml();
    }

    /**
     * Get row total including tax html
     *
     * @param Item $item
     * @return string
     */
    public function getActions(Item $item)
    {
        /** @var Actions $block */
        $block = $this->getChildBlock('actions');
        if ($block instanceof Actions) {
            $block->setItem($item);
            return $block->toHtml();
        } else {
            return '';
        }
    }

    /**
     * Retrieve product image
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param string $imageId
     * @param array $attributes
     * @return \Magento\Catalog\Block\Product\Image
     */
    public function getImage($product, $imageId, $attributes = [])
    {
        return $this->imageBuilder->setProduct($product)
            ->setImageId($imageId)
            ->setAttributes($attributes)
            ->create();
    }

    /**
     * Prepare SKU
     *
     * @param string $sku
     * @return string
     */
    public function prepareSku($sku)
    {
        return $this->escapeHtml($this->string->splitInjection($sku));
    }

    /**
     * Return sku of order item.
     *
     * @return string
     */
    public function getSku()
    {
        return $this->getItem()->getSku();
    }


    public function getItemPriceHtml($item = null)
    {
        $block = $this->getLayout()->getBlock('quote.item.price.unit');
        if (!$item) {
            $item = $this->getItem();
        }
        $block->setItem($item);
        return $block->toHtml();
    }
}
