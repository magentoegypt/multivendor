<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/21/2016
 * Time: 05:01 PM
 */
namespace Vnecoms\VendorsShippingTableRate\Controller\Vendors\Tablerate;

use Vnecoms\Vendors\Controller\Vendors\Action;
use Vnecoms\Vendors\App\Action\Context;
use Magento\Cms\Controller\Adminhtml\Page\PostDataProcessor;
use Magento\Framework\App\Request\DataPersistorInterface;

class SaveImport extends Action
{

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var PostDataProcessor
     */
    protected $dataProcessor;

    /**
     * @var \Vnecoms\VendorsShippingTableRate\Model\ResourceModel\Tablerate
     */
    protected $_resouce;

    /**
     * Save constructor.
     * @param Context $context
     * @param PostDataProcessor $dataProcessor
     * @param \Vnecoms\VendorsShippingTableRate\Model\ResourceModel\TablerateFactory $rate
     */
    public function __construct(
        Context $context,
        PostDataProcessor $dataProcessor,
        \Vnecoms\VendorsShippingTableRate\Model\ResourceModel\TablerateFactory $rate,
        DataPersistorInterface $dataPersistor
    ){
        $this->dataProcessor = $dataProcessor;
        $this->_resouce        = $rate;
        $this->dataPersistor = $dataPersistor;
        parent::__construct($context);
    }


    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data) {
            /** @var \Vnecoms\VendorsShippingTableRate\Model\TablerateFactory $model */
            $model = $this->_resouce->create();
            try {
                $model->uploadAndImport($this->_session->getVendor()->getId());
                $this->messageManager->addSuccess(__('You imported this file.'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }
        return $resultRedirect->setPath('*/*/');
    }
}
