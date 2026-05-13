<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsProductImportExport\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class Data extends AbstractHelper
{
    const XML_PATH_IMPORT_BUNCH_SIZE    = 'vendors/import_export/import_bunch_size';
    const XML_PATH_IMPORT_SIZE          = 'vendors/import_export/import_size';
    const XML_PATH_EXCEL_SHEET_NAME     = 'vendors/import_export/excel_sheet_name';
    
    const XML_PATH_MULTI_VALUE_SEPARATOR = 'product_import_export/general/multiple_value_separator';
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    
    /**
     * These attributes will not be exported
     * @var array
     */
    protected $_notExportAttributes;

    /**
     * @var array
     */
    protected $utf8Attributes;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Vnecoms\Vendors\Helper\Email $emailHelper
     * @param array $notExportAttributes
     * @param array $utf8Attributes
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Vnecoms\Vendors\Helper\Email $emailHelper,
        array $notExportAttributes = [],
        array $utf8Attributes = []
    ) {
        parent::__construct($context);
        $this->_notExportAttributes = $notExportAttributes;
        $this->utf8Attributes = $utf8Attributes;
        $this->scopeConfig = $context->getScopeConfig();
    }
    
    /**
     * Get import bunch size
     *
     * @return \Magento\Framework\App\Config\mixed
     */
    public function getImportBunchSize()
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_IMPORT_BUNCH_SIZE);
    }
    
    /**
     * Get import size
     *
     * @return \Magento\Framework\App\Config\mixed
     */
    public function getImportSize()
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_IMPORT_SIZE);
    }
    
    /**
     * Get not export attributes
     *
     * @return multitype:
     */
    public function getNotExportAttributes()
    {
        return $this->_notExportAttributes;
    }

    /**
     * @return array
     */
    public function getUtf8Attribute()
    {
        return $this->utf8Attributes;
    }
    
    /**
     * Get allowed image extensions
     *
     * @return multitype:string
     */
    public function getAllowedExtensions()
    {
        return ['jpg', 'jpeg', 'gif', 'png'];
    }
    
    /**
     * @return string
     */
    public function getSheetName(){
        return $this->scopeConfig->getValue(self::XML_PATH_EXCEL_SHEET_NAME);
    }
}
