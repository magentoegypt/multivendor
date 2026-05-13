<?php
/**
 * *
 *  * Product.php
 *  *
 *  * @file        Product.php
 *  * @copyright   Copyright (c) 2017 Vnecoms (https://www.vnecoms.com)
 *  * @site        https://www.vnecoms.com/
 *  * @author      Vnecoms <support@vnecoms.com>
 *  * @author      HiepNT <hiepnt@vnecoms.com>
 *
 */

/**
 * *
 *  * Product.php
 *  *
 *  * @file        Product.php
 *  * @copyright   Copyright (c) 2017 Vnecoms (https://www.vnecoms.com)
 *  * @site        https://www.vnecoms.com/
 *  * @author      Vnecoms <support@vnecoms.com>
 *  * @author      HiepNT <hiepnt@vnecoms.com>
 *
 */

namespace Vnecoms\VendorsCategory\Block\Vendors\Category\Tab;

use Vnecoms\Vendors\Block\Vendors\Widget\Grid\Grid;
use Vnecoms\Vendors\Block\Vendors\Widget\Grid\Column;
use Vnecoms\Vendors\Block\Vendors\Widget\Grid\Extended;

class Product extends \Vnecoms\Vendors\Block\Vendors\Widget\Grid\Extended
{
    //    protected $_template = 'Vnecoms_Vendors::widget/grid.phtml';

    /**
     * Core registry.
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    protected $vendorSession;

    protected $categoryCollection;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Vnecoms\VendorsCategory\Model\ResourceModel\Category\Collection $collection,
        array $data = []
    ) {
        $this->_productFactory = $productFactory;
        $this->_coreRegistry = $coreRegistry;
        $this->categoryCollection = $collection;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('category_products');
        $this->setDefaultSort('p_entity_id');
        $this->setSaveParametersInSession(false);
        $this->setUseAjax(true);
    }

    /**
     * @return array|null
     */
    public function getCategory()
    {
        return $this->_coreRegistry->registry('category');
    }

    /**
     * @param Column $column
     *
     * @return $this
     */
    protected function _addColumnFilterToCollection($column)
    {
        // Set custom filter for in category flag
        if ($column->getId() == 'p_in_category') {
            $productIds = $this->_getSelectedProducts();
            if (empty($productIds)) {
                $productIds = 0;
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('entity_id', ['in' => $productIds]);
            } elseif (!empty($productIds)) {
                $this->getCollection()->addFieldToFilter('entity_id', ['nin' => $productIds]);
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }

        return $this;
    }

    /**
     * @return Grid
     */
    protected function _prepareCollection()
    {
        if ($this->getCategory()->getId()) {
            $this->setDefaultFilter(['p_in_category' => 1]);
        }
        $collection = $this->_productFactory->create()->getCollection()->addAttributeToSelect(
            'name'
        )->addAttributeToSelect(
            'sku'
        )->addAttributeToSelect(
            'price'
        )->addAttributeToSelect(
            'visibility'
        )
        ->joinField(
            'position',
            $this->categoryCollection->getTable('ves_vendorscategory_category_product'),
            'position',
            'product_id=entity_id',
            'category_id='.(int) $this->getRequest()->getParam('id', 0),
            'left'
        )
        ->addAttributeToFilter('visibility', \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH);

        $vendorId = (int) $this->getVendor()->getId();
        if ($vendorId > 0) {
            $collection->addAttributeToFilter('vendor_id', $vendorId);
        }
        $this->setCollection($collection);

        $productIds = $this->_getSelectedProducts();
        if (empty($productIds)) {
            $productIds = 0;
        }
      //  $this->getCollection()->addFieldToFilter('entity_id', ['in' => $productIds]);

     //   echo $this->getCollection()->getSelect();
        return parent::_prepareCollection();
    }

    /**
     * @return Extended
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'p_in_category',
            [
                'type' => 'checkbox',
                'name' => 'product[in_category]',
                'values' => $this->_getSelectedProducts(),
                'index' => 'entity_id',
                'header_css_class' => 'col-select col-massaction',
                'column_css_class' => 'col-select col-massaction',
            ]
        );
        $this->addColumn(
            'p_entity_id',
            [
                'header' => __('ID'),
                'sortable' => true,
                'name' => 'product[entity_id]',
                'index' => 'entity_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
            ]
        );
        $this->addColumn('p_name', ['header' => __('Name'),'name' => 'product[name]', 'index' => 'name']);
        $this->addColumn('p_sku', ['header' => __('SKU'),'name' => 'product[sku]', 'index' => 'sku']);
        $this->addColumn(
            'p_price',
            [
                'header' => __('Price'),
                'type' => 'currency',
                'currency_code' => (string) $this->_scopeConfig->getValue(
                    \Magento\Directory\Model\Currency::XML_PATH_CURRENCY_BASE,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                ),
                'name' => 'product[price]',
                'index' => 'price',
            ]
        );
        $this->addColumn(
            'p_position',
            [
                'header' => __('Position'),
                'type' => 'number',
                'index' => 'position',
                'name' => 'product[position]',
                'editable' => true,
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('catalog/*/grid', ['_current' => true]);
    }

    /**
     * @return array
     */
    protected function _getSelectedProducts()
    {
        $products = $this->getRequest()->getPost('selected_products');
        if ($products === null) {
            $products = $this->getCategory()->getProductsPosition();

            return array_keys($products);
        }

        return $products;
    }

    /**
     * @return Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor()
    {
        if (!$this->vendorSession) {
            $this->vendorSession = \Magento\Framework\App\ObjectManager::getInstance()->create(
                'Vnecoms\Vendors\Model\Session'
            );
        }

        return $this->vendorSession->getVendor();
    }
}
