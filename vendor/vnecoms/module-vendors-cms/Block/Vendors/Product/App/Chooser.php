<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Product Chooser for "Product Link" Cms Widget Plugin.
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */

namespace Vnecoms\VendorsCms\Block\Vendors\Product\App;

use Magento\Framework\Data\Form\Element\AbstractElement;

class Chooser extends \Magento\Catalog\Block\Adminhtml\Product\Widget\Chooser
{
    protected $_template = 'Vnecoms_Vendors::widget/grid/extended.phtml';
    /**
     * @var \Vnecoms\Vendors\Model\Session
     */
    protected $vendorSession;

    /**
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor()
    {
        if (!$this->vendorSession) {
            $this->vendorSession = \Magento\Framework\App\ObjectManager::getInstance()
                ->get('Vnecoms\Vendors\Model\Session')->getVendor();
        }

        return $this->vendorSession;
    }

    /**
     * Prepare chooser element HTML
     *
     * @param AbstractElement $element Form Element
     * @return AbstractElement
     */
    public function prepareElementHtml(AbstractElement $element)
    {
        $uniqId = $this->mathRandom->getUniqueHash($element->getId());
        $sourceUrl = $this->getUrl(
            'cms/app_product/chooser',
            ['uniq_id' => $uniqId, 'use_massaction' => false]
        );

        $chooser = $this->getLayout()->createBlock(
            'Vnecoms\VendorsCms\Block\Vendors\App\Chooser'
        )->setElement(
            $element
        )->setConfig(
            $this->getConfig()
        )->setFieldsetId(
            $this->getFieldsetId()
        )->setSourceUrl(
            $sourceUrl
        )->setUniqId(
            $uniqId
        );

        if ($element->getValue()) {
            $value = explode('/', $element->getValue());
            $productId = false;
            if (isset($value[0]) && isset($value[1]) && $value[0] == 'product') {
                $productId = $value[1];
            }
            $categoryId = isset($value[2]) ? $value[2] : false;
            $label = '';
            if ($categoryId) {
                $label = $this->_resourceCategory->getAttributeRawValue(
                    $categoryId,
                    'name',
                    $this->_storeManager->getStore()
                ) . '/';
            }
            if ($productId) {
                $label .= $this->_resourceProduct->getAttributeRawValue(
                    $productId,
                    'name',
                    $this->_storeManager->getStore()
                );
            }
            $chooser->setLabel($label);
        }

        $element->setData('after_element_html', $chooser->toHtml());
        return $element;
    }

    /**
     * Adds additional parameter to URL for loading only products grid
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl(
            'cms/app_product/chooser',
            [
                'products_grid' => true,
                '_current' => true,
                'uniq_id' => $this->getId(),
                'use_massaction' => $this->getUseMassaction(),
                'product_type_id' => $this->getProductTypeId()
            ]
        );
    }

    /**
     * Prepare products collection, defined collection filters (category, product type)
     *
     * @return Extended
     */
    protected function _prepareCollection()
    {
        /* @var $collection \Magento\Catalog\Model\ResourceModel\Product\Collection */
        $collection = $this->_collectionFactory->create()->setStoreId(0)->addAttributeToSelect('name');

        if ($categoryId = $this->getCategoryId()) {
            $category = $this->_categoryFactory->create()->load($categoryId);
            if ($category->getId()) {
                // $collection->addCategoryFilter($category);
                $productIds = $category->getProductsPosition();
                $productIds = array_keys($productIds);
                if (empty($productIds)) {
                    $productIds = 0;
                }
                $collection->addFieldToFilter('entity_id', ['in' => $productIds]);
            }
        }

        if ($productTypeId = $this->getProductTypeId()) {
            $collection->addAttributeToFilter('type_id', $productTypeId);
        }
        $collection->addAttributeToFilter('vendor_id', '2');

        $this->setCollection($collection);
    }
}
