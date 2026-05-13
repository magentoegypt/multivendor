<?php

namespace Vnecoms\VendorsProductImportExport\Controller\Vendors\Import;

use Vnecoms\VendorsProductImportExport\Model\Import\Adapter as ImportAdapter;
use Magento\Framework\App\Filesystem\DirectoryList;
use Vnecoms\VendorsProductImportExport\Model\Import;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\ResultFactory;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\Import\Entity\AbstractEntity;
use Vnecoms\VendorsProductImportExport\Helper\Data as Helper;

class Upload extends \Vnecoms\Vendors\Controller\Vendors\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_Vendors::product_import';
    
    const IMPORT_HISTORY_FILE_DOWNLOAD_ROUTE = '*/history/download';
    
    /**
     * Limit view errors
     */
    const LIMIT_ERRORS_MESSAGE = 100;
    
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;
    
    /**
     * @var Import
     */
    private $import;
    
    /**
     * @var \Vnecoms\VendorsProductImportExport\Helper\Data
     */
    protected $helper;
    
    /**
     * @param \Vnecoms\Vendors\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Vnecoms\VendorsProductImportExport\Helper\Data $helper
     */
    public function __construct(
        \Vnecoms\Vendors\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Vnecoms\VendorsProductImportExport\Helper\Data $helper
    ) {
        parent::__construct($context);
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->helper = $helper;
    }

    /**
     * Validate uploaded files action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        /** @var \Magento\Framework\View\Result\Layout $resultLayout */
        $resultLayout = $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
        /** @var $resultBlock ImportResultBlock */
        $resultBlock = $resultLayout->getLayout()->getBlock('import.result');
        if ($data) {
/*             if($this->isExcelImport()){
                return $this->processExcelImport($resultLayout);
            }
             */
            $data[Import::FIELD_FIELD_SEPARATOR] = Import::FIELD_SEPARATOR;
            /** @var \Vnecoms\VendorsConfig\Helper\Data */
            $configHelper = $this->_objectManager->create('Vnecoms\VendorsConfig\Helper\Data');
            $multiValueSeparator = $configHelper->getVendorConfig(Helper::XML_PATH_MULTI_VALUE_SEPARATOR, $this->_session->getVendor()->getId());
            $data[Import::FIELD_FIELD_MULTIPLE_VALUE_SEPARATOR] = $multiValueSeparator;
            $data[Import::FIELD_NAME_VALIDATION_STRATEGY] =
                \Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface::VALIDATION_STRATEGY_SKIP_ERRORS;
            
            /** @var $import \Magento\ImportExport\Model\Import */
            $import = $this->getImport()->setData($data);
            try {
                $source = ImportAdapter::findAdapterFor(
                    $import->uploadSource(),
                    $this->_objectManager->create('Magento\Framework\Filesystem')
                        ->getDirectoryWrite(DirectoryList::ROOT),
                    $this->isExcelImport()?$this->helper->getSheetName():$import::FIELD_SEPARATOR
                );
                $this->processValidationResult($import->validateSource($source), $resultBlock);
            } catch (LocalizedException $e) {
                $resultBlock->addError($e->getMessage());
            } catch (\Exception $e) {
                $resultBlock->addError(__('Sorry, but the data is invalid or the file is not uploaded.'));
            }
            return $resultLayout;
        } elseif ($this->getRequest()->isPost()) {
            $resultBlock->addError(__('The file was not uploaded.'));
            return $resultLayout;
        }
        
        $this->messageManager->addError(__('Sorry, but the data is invalid or the file is not uploaded.'));
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('catalog/import/index');
        return $resultRedirect;
    }

    /**
     * @param bool $validationResult
     * @param ImportResultBlock $resultBlock
     * @return void
     */
    private function processValidationResult($validationResult, $resultBlock)
    {
        $import = $this->getImport();
        if (!$import->getProcessedRowsCount()) {
            if (!$import->getErrorAggregator()->getErrorsCount()) {
                $resultBlock->addError(__('This file is empty. Please try another one.'));
            } else {
                foreach ($import->getErrorAggregator()->getAllErrors() as $error) {
                    $resultBlock->addError($error->getErrorMessage());
                }
            }
        } else {
            $errorAggregator = $import->getErrorAggregator();
            if (!$validationResult) {
                $resultBlock->addError(
                    __('Data validation failed. Please fix the following errors and upload the file again.')
                );
            } else {
                if ($import->isImportAllowed()) {
                    $resultBlock->addSuccess(
                        __('File is valid. All rows have been added to import queue! To start import '),
                        true
                    );
                } else {
                    $resultBlock->addError(__('The file is valid, but we can\'t import it for some reason.'));
                }
            }
			$this->addErrorMessages($resultBlock, $errorAggregator);
            $resultBlock->addNotice(
                __(
                    'Checked rows: %1, checked entities: %2, invalid rows: %3, total errors: %4',
                    $import->getProcessedRowsCount(),
                    $import->getProcessedEntitiesCount(),
                    $errorAggregator->getInvalidRowsCount(),
                    $errorAggregator->getErrorsCount()
                )
            );
        }
    }

    /**
     * @param \Magento\Framework\View\Element\AbstractBlock $resultBlock
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @return $this
     */
    protected function addErrorMessages(
        \Magento\Framework\View\Element\AbstractBlock $resultBlock,
        ProcessingErrorAggregatorInterface $errorAggregator
    ) {
        if ($errorAggregator->getErrorsCount()) {
            $message = '';
            $counter = 0;
            foreach ($this->getErrorMessages($errorAggregator) as $error) {
                $message .= ++$counter . '. ' . $error . '<br>';
                if ($counter >= self::LIMIT_ERRORS_MESSAGE) {
                    break;
                }
            }
            if ($errorAggregator->hasFatalExceptions()) {
                foreach ($this->getSystemExceptions($errorAggregator) as $error) {
                    $message .= $error->getErrorMessage()
                    . ' <a href="#" onclick="$(this).next().show();$(this).hide();return false;">'
                        . __('Show more') . '</a><div style="display:none;">' . __('Additional data') . ': '
                            . $error->getErrorDescription() . '</div>';
                }
            }
            try {
                $resultBlock->addNotice(
                    '<strong>' . __('Following Error(s) has been occurred during importing process:') . '</strong><br>'
                    . '<div class="import-error-wrapper">' . __('Only the first 100 errors are shown. ')
                    /* . '<a href="'
                    . $this->createDownloadUrlImportHistoryFile($this->createErrorReport($errorAggregator))
                    . '">' . __('Download full report') . '</a><br>' */
                    . '<div class="import-error-list">' . $message . '</div></div>'
                );
            } catch (\Exception $e) {
                foreach ($this->getErrorMessages($errorAggregator) as $errorMessage) {
                    $resultBlock->addError($errorMessage);
                }
            }
        }
    
        return $this;
    }
    
    /**
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @return \Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError[]
     */
    protected function getSystemExceptions(ProcessingErrorAggregatorInterface $errorAggregator)
    {
        return $errorAggregator->getErrorsByCode([AbstractEntity::ERROR_CODE_SYSTEM_EXCEPTION]);
    }
    
    /**
     * @param \Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface $errorAggregator
     * @return array
     */
    protected function getErrorMessages(ProcessingErrorAggregatorInterface $errorAggregator)
    {
        $messages = [];
        $rowMessages = $errorAggregator->getRowsGroupedByErrorCode([], [AbstractEntity::ERROR_CODE_SYSTEM_EXCEPTION]);
        foreach ($rowMessages as $errorCode => $rows) {
            $messages[] = $errorCode . ' ' . __('in row(s):') . ' ' . implode(', ', $rows);
        }
        return $messages;
    }
    
    /**
     * @param string $fileName
     * @return string
     */
    protected function createDownloadUrlImportHistoryFile($fileName)
    {
        return $this->getUrl(self::IMPORT_HISTORY_FILE_DOWNLOAD_ROUTE, ['filename' => $fileName]);
    }
    
    /**
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @return string
     */
    protected function createErrorReport(ProcessingErrorAggregatorInterface $errorAggregator)
    {
        
        return '';
    }
    
    /**
     * @return Import
     * @deprecated
     */
    private function getImport()
    {
        if (!$this->import) {
            $this->import = $this->_objectManager->get(Import::class);
            $this->import->setVendor($this->_session->getVendor());
        }
        return $this->import;
    }
    
    /**
     * Check if the uploaded file is excel file
     * @return boolean
     */
    protected function isExcelImport(){
        if(!isset($_FILES['import_file']['name'])) return false;
        
        $fileName = explode(".", $_FILES['import_file']['name']);
        return in_array('xlsx', $fileName) || in_array('xls', $fileName);
    }
    
    /**
     * Process Excel file import
     * 
     * @param \Magento\Framework\View\Result\Layout $resultLayout
     * @return \Magento\Framework\View\Result\Layout
     */
    protected function processExcelImport(\Magento\Framework\View\Result\Layout $resultLayout){
        /** @var $resultBlock ImportResultBlock */
        $resultBlock = $resultLayout->getLayout()->getBlock('import.result');
        $import = $this->_objectManager->create('Vnecoms\VendorsProductImportExport\Model\Import\Excel');
        /* try { */
            $import->setVendor($this->_session->getVendor());
            $source = ImportAdapter::findAdapterFor(
                $import->uploadSource(),
                $this->_objectManager->create('Magento\Framework\Filesystem')
                ->getDirectoryWrite(DirectoryList::ROOT),
                $import->getSheetName()
            );
            
            $import->process($source);
       /*  } catch (LocalizedException $e) {
            $resultBlock->addError($e->getMessage());
        } catch (\Exception $e) {
            $resultBlock->addError(__('Sorry, but the data is invalid or the file is not uploaded.'));
        } */
        return $resultLayout;
    }
}
