<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Vnecoms\VendorsProductImportExport\Model\Export;

use Magento\Framework\App\Filesystem\DirectoryList;
use Vnecoms\VendorsProductImportExport\Model\Import as Import;
use Magento\CatalogImportExport\Model\Import\Product\RowValidatorInterface as ValidatorInterface;
use Vnecoms\VendorsProductImportExport\Model\ResourceModel\Import\Data\Collection as ImportSource;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\Stdlib\DateTime;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;
use Magento\ImportExport\Model\Import\Entity\AbstractEntity;

class Product extends \Magento\CatalogImportExport\Model\Export\Product
{
    /**
     * @var \Vnecoms\Vendors\Model\Vendor
     */
    protected $_vendor;

    /**
     * @var \Vnecoms\VendorsProductImportExport\Helper\Data
     */
    protected $_exportHelper;
    
    /**
     * Get export helper
     * 
     * @return \Vnecoms\VendorsProductImportExport\Helper\Data
     */
    public function getExportHelper(){
        if(!$this->_exportHelper){
            $om = \Magento\Framework\App\ObjectManager::getInstance();
            $this->_exportHelper = $om->create('Vnecoms\VendorsProductImportExport\Helper\Data');
        }
        
        return $this->_exportHelper;
    }
    
    /**
     * Set Vendor
     *
     * @param \Vnecoms\Vendors\Model\Vendor $vendor
     */
    public function setVendor(\Vnecoms\Vendors\Model\Vendor $vendor){
        $this->_vendor = $vendor;
        return $this;
    }
    
    /**
     * Get Vendor
     *
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor(){
        return $this->_vendor;
    }
    
    /**
     * Get attributes codes which are appropriate for export.
     *
     * @return array
     */
    protected function _getExportAttrCodes()
    {
        if (null === self::$attrCodes) {
            if (!empty($this->_parameters[\Magento\ImportExport\Model\Export::FILTER_ELEMENT_SKIP]) && is_array(
                $this->_parameters[\Magento\ImportExport\Model\Export::FILTER_ELEMENT_SKIP]
            )
            ) {
                $skipAttr = array_flip($this->_parameters[\Magento\ImportExport\Model\Export::FILTER_ELEMENT_SKIP]);
            } else {
                $skipAttr = [];
            }
            $attrCodes = [];
            $notAllowedAttributes = $this->getExportHelper()->getNotExportAttributes();
            foreach ($this->filterAttributeCollection($this->getAttributeCollection()) as $attribute) {
                if (
                    (!isset(
                        $skipAttr[$attribute->getAttributeId()]
                    ) && !in_array($attribute->getAttributeCode(),$notAllowedAttributes)
                ) || in_array(
                    $attribute->getAttributeCode(),
                    $this->_permanentAttributes
                )
                ) {
                    $attrCodes[] = $attribute->getAttributeCode();
                }
            }
            self::$attrCodes = $attrCodes;
        }
        return self::$attrCodes;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function _getEntityCollection($resetCollection = false)
    {
        if ($resetCollection || empty($this->_entityCollection)) {
            $this->_entityCollection = $this->_entityCollectionFactory->create();
            $this->_entityCollection->addAttributeToFilter('vendor_id',$this->getVendor()->getId());
        }
        return $this->_entityCollection;
    }
    
    /**
     *
     * Multiple value separator getter.
     * @return string
     */
    public function getMultipleValueSeparator()
    {
        if (!empty($this->_parameters[Import::FIELD_FIELD_MULTIPLE_VALUE_SEPARATOR])) {
            return $this->_parameters[Import::FIELD_FIELD_MULTIPLE_VALUE_SEPARATOR];
        }
        return Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR;
    }
    
    /**
     * Update data row with information about categories. Return true, if data row was updated
     *
     * @param array &$dataRow
     * @param array &$rowCategories
     * @param int $productId
     * @return bool
     */
    protected function updateDataWithCategoryColumns(&$dataRow, &$rowCategories, $productId)
    {
        if (!isset($rowCategories[$productId])) {
            return false;
        }
        $categories = [];
        foreach ($rowCategories[$productId] as $categoryId) {
            $categoryPath = $this->_rootCategories[$categoryId];
            if (isset($this->_categories[$categoryId])) {
                $categoryPath .= '/' . $this->_categories[$categoryId];
            }
            $categories[] = $categoryPath;
        }
        $dataRow[self::COL_CATEGORY] = implode($this->getMultipleValueSeparator(), $categories);
        unset($rowCategories[$productId]);
    
        return true;
    }
    
    /**
     * Initialize attribute option values and types.
     *
     * @return $this
     */
    protected function initAttributes(){
        parent::initAttributes();
        foreach($this->_attributeTypes as $code=>$type){
            if($type == 'datetime'){
                $this->_attributeTypes[$code] = 'varchar';
            }
        }
        return $this;
    }
}
