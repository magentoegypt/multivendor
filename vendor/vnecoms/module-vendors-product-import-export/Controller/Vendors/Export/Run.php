<?php

namespace Vnecoms\VendorsProductImportExport\Controller\Vendors\Export;

use Magento\Framework\Controller\ResultFactory;
use Vnecoms\Vendors\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\ImportExport\Model\Export as ExportModel;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\ImportExport\Model\Import;
use Vnecoms\VendorsProductImportExport\Helper\Data as Helper;

class Run extends \Vnecoms\Vendors\Controller\Vendors\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_Vendors::product_import_media';
    
    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $fileFactory;
    
    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     */
    public function __construct(
        Context $context,
        FileFactory $fileFactory
    ) {
        $this->fileFactory = $fileFactory;
        parent::__construct($context);
    }
    
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
        
        try {
            /** @var \Vnecoms\VendorsConfig\Helper\Data */
            $configHelper = $this->_objectManager->create('Vnecoms\VendorsConfig\Helper\Data');
            $multiValueSeparator = $configHelper->getVendorConfig(Helper::XML_PATH_MULTI_VALUE_SEPARATOR, $this->_session->getVendor()->getId());
            $params = [
                'entity' => 'catalog_product',
                'file_format' => $this->getRequest()->getParam('file_format'),
                'fields_enclosure' => $this->getRequest()->getParam('fields_enclosure'),
                'export_filter' => [],
                Import::FIELD_FIELD_MULTIPLE_VALUE_SEPARATOR => $multiValueSeparator,
            ];
            
            /** @var $model \Magento\ImportExport\Model\Export */
            $model = $this->_objectManager->create('Vnecoms\VendorsProductImportExport\Model\Export');
            $model->setData($params);
            $model->setVendor($this->_session->getVendor());

            return $this->fileFactory->create(
                $model->getFileName(),
                $model->export(),
                DirectoryList::VAR_DIR,
                $model->getContentType()
            );
        } catch (LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
            $this->messageManager->addError(__('Please correct the data sent value.'));
        }
        /* Revert locale*/
        $localeResolver->setLocale($currentLocale);
        
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('*/*/index');
        return $resultRedirect;
    }
}
