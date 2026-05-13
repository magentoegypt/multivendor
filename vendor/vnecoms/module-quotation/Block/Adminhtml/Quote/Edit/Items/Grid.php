<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\Quotation\Block\Adminhtml\Quote\Edit\Items;

use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Model\Spi\StockRegistryProviderInterface;
use Magento\CatalogInventory\Model\Spi\StockStateProviderInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Vnecoms\Quotation\Model\Item;
use Magento\Framework\Pricing\PriceCurrencyInterface;

class Grid extends \Vnecoms\Quotation\Block\Adminhtml\Quote\Edit\AbstractQuote
{
    /**
     * Catalog product configuration
     *
     * @var \Magento\Catalog\Helper\Product\Configuration
     */
    protected $productConfig = null;

    /**
     * Bundle catalog product configuration
     *
     * @var Configuration
     */
    protected $bundleConfig = null;

    /**
     * @var StockStateProviderInterface
     */
    protected $stockStateProvider;

    /**
     * @var StockRegistryProviderInterface
     */
    protected $stockRegistryProvider;

    /**
     * @var StockConfigurationInterface
     */
    protected $stockConfiguration;

    /**
     * @var GetSalableQuantityDataBySku
     */
    private $moduleManager;

    protected $objectmanager;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Vnecoms\Quotation\Model\Backend\Session $sessionQuote
     * @param \Vnecoms\Quotation\Model\Quote $quote
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Catalog\Helper\Product\Configuration $productConfig
     * @param \Magento\Bundle\Helper\Catalog\Product\Configuration $bundleConfig
     * @param StockStateProviderInterface $stockStateProvider
     * @param StockRegistryProviderInterface $stockRegistryProvider
     * @param StockConfigurationInterface $stockConfiguration
     * @param \Magento\Framework\ObjectManagerInterface $objectmanager
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Vnecoms\Quotation\Model\Backend\Session $sessionQuote,
        \Vnecoms\Quotation\Model\Quote $quote,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Helper\Product\Configuration $productConfig,
        \Magento\Bundle\Helper\Catalog\Product\Configuration $bundleConfig,
        StockStateProviderInterface $stockStateProvider,
        StockRegistryProviderInterface $stockRegistryProvider,
        StockConfigurationInterface $stockConfiguration,
        \Magento\Framework\ObjectManagerInterface $objectmanager,
        \Magento\Framework\Module\Manager $moduleManager,
        array $data = []
    ) {
        parent::__construct($context, $sessionQuote, $quote, $priceCurrency, $registry, $data);
        $this->productConfig = $productConfig;
        $this->bundleConfig = $bundleConfig;
        $this->stockStateProvider = $stockStateProvider;
        $this->stockRegistryProvider = $stockRegistryProvider;
        $this->stockConfiguration = $stockConfiguration;
        $this->moduleManager = $moduleManager;
        $this->objectmanager = $objectmanager;
    }

    protected function _construct()
    {
        parent::_construct();
        $this->setId('quote_search_grid');
    }

    /**
     * Get store
     *
     * @return \Magento\Store\Model\Store
     */
    public function getStore()
    {
        return $this->getRealQuote()->getStore();
    }

    /**
     * Get ALl Items
     */
    public function getItems()
    {
        return $this->getParentBlock()->getItems();
    }

    /**
     * Get Item Unit Price Html
     *
     * @param Item $item
     * @return string
     */
    public function getItemUnitPriceHtml(Item $item)
    {
        $block = $this->getLayout()->getBlock('item_unit_price');
        $block->setItem($item);
        return $block->toHtml();
    }

    /**
     * Get Item Proposal Html
     *
     * @param Item $item
     * @return string
     */
    public function getItemProposalHtml(Item $item)
    {
        $block = $this->getLayout()->getBlock('item_proposal');
        $block->setItem($item);
        return $block->toHtml();
    }


    /**
     * @param $item
     * @return array|array[]
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException
     */
    public function getStockItem($item) {
        $product = $item->getProduct();


        if ($this->moduleManager->isEnabled('Magento_InventorySalesAdminUi')) {
            $getSalableQtyDataBySku = $this->objectmanager
                ->create(\Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku::class);
            $salable = $getSalableQtyDataBySku->execute($product->getSku());
            if ($salable) {
                return $salable;
            }
        }

        $scopeId = $this->stockConfiguration->getDefaultScopeId();
        $stockItem = $this->stockRegistryProvider->getStockItem($product->getId(), $scopeId);
        if (!$stockItem->getManageStock()) {
            $qty = "unlimited";
        } else {
            $qty = $this->stockStateProvider->getStockQty($stockItem);
        }
        return [
            [
                "stock_name" => __("Default Stock"),
                "qty" => $qty
            ]
        ];

    }

    /**
     * Get item remove URL
     *
     * @param Item $item
     * @return string
     */
    public function getRemoveItemUrl(Item $item)
    {
        return $this->getUrl('');
    }

    /**
     * @param Item $item
     * @return bool
     */
    public function itemHasRemark(Item $item)
    {
        return (bool) $item->getComment();
    }

    /**
     * Get order item extra info block
     *
     * @param Item $item
     * @return \Magento\Framework\View\Element\AbstractBlock
     */
    public function getItemExtraInfo($item)
    {
        return $this->getLayout()->getBlock('quote_item_extra_info')->setItem($item);
    }

    /**
     * Return html button which calls configure window
     *
     * @param \Vnecoms\Quotation\Model\Item $item
     * @return string
     */
    public function getConfigureButtonHtml(\Vnecoms\Quotation\Model\Item $item)
    {
        $product = $item->getProduct();

        $options = ['label' => __('Configure')];
        if ($product->canConfigure()) {
            $options['onclick'] = sprintf('quote.showQuoteItemConfiguration(%s)', $item->getId());
        } else {
            $options['class'] = ' disabled';
            $options['title'] = __('This product does not have any configurable options');
        }

        return $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')->setData($options)->toHtml();
    }

    /**
     * Get list of all otions for product
     *
     * @param \Vnecoms\Quotation\Model\Item $item
     * @return Ambigous <multitype:, multitype:multitype:NULL string Ambigous <string, \Magento\Framework\mixed, NULL, multitype:>  >
     */
    public function getOptionList(\Vnecoms\Quotation\Model\Item $item)
    {
        return $this->getProductOptions($item);
    }

    /**
     * Get product customize options
     *
     * @param \Vnecoms\Quotation\Model\Item $item
     * @return Ambigous <multitype:, multitype:multitype:NULL string Ambigous <string, \Magento\Framework\mixed, NULL, multitype:>  >
     */
    public function getProductOptions(\Vnecoms\Quotation\Model\Item $item)
    {
        /* @var $helper \Magento\Catalog\Helper\Product\Configuration */
        $helper = $this->productConfig;
        switch($item->getProductType()){
            case \Magento\Catalog\Model\Product\Type::DEFAULT_TYPE:
            case \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL:
                return $helper->getCustomOptions($item);
            case \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE:
                return $this->bundleConfig->getOptions($item);
            case \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE:
                return $this->productConfig->getOptions($item);
        }
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
        $helper = $this->productConfig;
        $params = [
            'max_length' => 55,
            'cut_replacer' => ' <a href="#" class="dots tooltip toggle" onclick="return false">...</a>'
        ];
        return $helper->getFormattedOptionValue($optionValue, $params);
    }

    /**
     * Get item original price
     *
     * @param Item $item
     * @return float
     */
    public function getItemOrigPrice($item)
    {
        return $this->convertPrice($item->getPrice());
    }

    /**
     * Get session
     *
     * @return SessionManagerInterface
     */
    public function getSession()
    {
        return $this->getParentBlock()->getSession();
    }
}
