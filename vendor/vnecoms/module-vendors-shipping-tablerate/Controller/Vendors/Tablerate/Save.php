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

class Save extends Action
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
     * @var \Vnecoms\VendorsShippingTableRate\Model\Tablerate
     */
    protected $_tablerate;

    /**
     * Save constructor.
     * @param Context $context
     * @param PostDataProcessor $dataProcessor
     * @param \Vnecoms\VendorsShippingTableRate\Model\TablerateFactory $rate
     */
    public function __construct(
        Context $context,
        PostDataProcessor $dataProcessor,
        \Vnecoms\VendorsShippingTableRate\Model\TablerateFactory $rate,
        DataPersistorInterface $dataPersistor
    ){
        $this->dataProcessor = $dataProcessor;
        $this->_tablerate         = $rate;
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
            $model = $this->_tablerate->create();

            $id = $this->getRequest()->getParam('rate_id');
            if (empty($data['rate_id'])) {
                $data['rate_id'] = null;
            }
            if ($id) {
                $model->load($id);
            }

            $data["vendor_id"] = $this->_session->getVendor()->getId();

            $region = !isset($data["dest_region_id"]) ? "*" : $data["dest_region_id"];
            $region = $region ? $region  : "*";
            $data["dest_region_id"] = $region;
            if($this->getRequest()->getParam('dest_zip_handle')){
                $data['dest_zip'] = $this->getRequest()->getParam('dest_zip_from').'-'.$this->getRequest()->getParam('dest_zip_to');
            }


            $model->setData($data);

            $this->_eventManager->dispatch(
                'table_rate_prepare_save',
                ['rate' => $model, 'request' => $this->getRequest()]
            );

            try {
                $errors = $model->validate();

                if($errors !== true){
                    throw new \Exception(implode("<br />", $errors));
                }

                $model->save();
                
                $this->messageManager->addSuccess(__('You saved this rate.'));
                $this->dataPersistor->clear('tablerate_rate');
                $this->_objectManager->get('Vnecoms\Vendors\Model\Session')->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['rate_id' => $model->getId(), '_current' => true]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->_objectManager->get('Vnecoms\Vendors\Model\Session')->setFormData($data);
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->_objectManager->get('Vnecoms\Vendors\Model\Session')->setFormData($data);
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->_objectManager->get('Vnecoms\Vendors\Model\Session')->setFormData($data);
                $this->messageManager->addError($e->getMessage());
            }

            $this->dataPersistor->set('tablerate_rate', $data);
            return $resultRedirect->setPath('*/*/edit', ['rate_id' => $this->getRequest()->getParam('rate_id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }
}
