<?php

namespace Vnecoms\VendorsProductImportExport\Controller\Vendors\Import;

use Vnecoms\VendorsProductImportExport\Model\Import\Data as ImportData;
use Magento\ImportExport\Model\Import;
use Vnecoms\VendorsProductImportExport\Helper\Data as Helper;

class Run extends \Vnecoms\Vendors\Controller\Vendors\Action
{    
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_Vendors::product_import';
    
    /**
     * Execute action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     * @throws \Magento\Framework\Exception\LocalizedException|\Exception
     */
    public function execute()
    {
        /* Set locale to en_US to fix date-time problem*/
        $localeResolver = $this->_objectManager->get('\Magento\Framework\Locale\ResolverInterface');
        $currentLocale = $localeResolver->getLocale();
        $localeResolver->setLocale('en_US');
        
        $vendor = $this->_session->getVendor();
        /** @var \Vnecoms\VendorsProductImportExport\Helper\Data */
        $helper = $this->_objectManager->create('Vnecoms\VendorsProductImportExport\Helper\Data');
        $importSize = $helper->getImportSize();
        /** @var \Vnecoms\VendorsProductImportExport\Model\ResourceModel\Import\Data\Collection */
        $importSource = $this->_objectManager->create('Vnecoms\VendorsProductImportExport\Model\ResourceModel\Import\Data\Collection');
        $importSource->addFieldToFilter('vendor_id', $vendor->getId())
            ->addFieldToFilter('status', ['in' =>[
                ImportData::STATUS_DRAFT,
            ]])
            ->setPageSize($importSize);
        $messages = [];
        
        /** @var \Vnecoms\VendorsProductImportExport\Model\Import\Product */
        $adapter = $this->_objectManager->create('Vnecoms\VendorsProductImportExport\Model\Import\Product');
        
        /** @var \Vnecoms\VendorsConfig\Helper\Data */
        $configHelper = $this->_objectManager->create('Vnecoms\VendorsConfig\Helper\Data');
        $multiValueSeparator = $configHelper->getVendorConfig(Helper::XML_PATH_MULTI_VALUE_SEPARATOR, $this->_session->getVendor()->getId());
        $adapter->setParameters([
            Import::FIELD_FIELD_MULTIPLE_VALUE_SEPARATOR => $multiValueSeparator
        ]);
        $adapter->setVendor($vendor);
        
        $messages = $adapter->import($importSource);
        
        $result = ['success' => true, 'processed_items' => $importSource->count(),'messages'=>$messages];
        
        /* Revert locale*/
        $localeResolver->setLocale($currentLocale);
        
        $this->getResponse()->setBody(json_encode($result));
    }
}
