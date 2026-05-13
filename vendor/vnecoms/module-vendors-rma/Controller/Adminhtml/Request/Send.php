<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsRMA\Controller\Adminhtml\Request;

use Vnecoms\RMA\Controller\Adminhtml\Request\Request;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Cms\Controller\Adminhtml\Page\PostDataProcessor;
use Vnecoms\RMA\Model\Source\Email\Type as EMAIL_TYPE;
use Vnecoms\RMA\Model\Source\Message\Type as MESSAGE_TYPE;

class Send extends Action
{
    /**
     * @var \Vnecoms\RMA\Model\RequestFactory
     */
    protected $requestFactory;

    /**
     * @var PostDataProcessor
     */
    protected $dataProcessor;

    /**
     * @var \Vnecoms\RMA\Helper\Config
     */

    protected $_helperRequest;

    /**
     * @var \Vnecoms\RMA\Model\StatusFactory
     */
    protected $status;
    /**
     * @param Context $context
     * @param PostDataProcessor $dataProcessor
     */
    public function __construct(
        Context $context,
        PostDataProcessor $dataProcessor ,
        \Vnecoms\RMA\Model\RequestFactory $requestFactory,
        \Vnecoms\RMA\Helper\Config $helperTicket,
        \Vnecoms\RMA\Model\StatusFactory $status
    )
    {
        $this->dataProcessor = $dataProcessor;
        $this->requestFactory = $requestFactory;
        $this->status        = $status;
        $this->_helperRequest = $helperTicket;
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
        if ($data["request_id"]) {
            $data = $this->dataProcessor->filter($data);
            $request  = $this->requestFactory->create();;
            $request->load($data["request_id"]);
            if (!$request->getId() ) {
                $this->messageManager->addError(__('This RMA no longer exists.'));
                return $resultRedirect->setPath('*/*/resolve', ['request_id' => $request->getId(), '_current' => true]);
            }
            $this->_eventManager->dispatch(
                'rma_request_prepare_save',
                ['rma' => $request, 'request' => $this->getRequest()]
            );
            if (!$this->dataProcessor->validate($data)) {
                return $resultRedirect->setPath('*/*/resolve', ['request_id' => $request->getId(), '_current' => true]);
            }
            $status = null;
            switch ($data["resolve_type"]){
                case "accept":
                    $status = $this->status->create()->load(\Vnecoms\RMA\Model\Request::STATUS_APPROVAL,"code");
                    break;
                case "resolve":
                    $status = $this->status->create()->load(\Vnecoms\RMA\Model\Request::STATUS_RESOLVED,"code");
                    break;
                case "deny":
                    $status = $this->status->create()->load(\Vnecoms\RMA\Model\Request::STATUS_CANCELED,"code");
                    break;
            }
            if (!$status) {
                $this->messageManager->addError(__('You must select state when resolve RMA.'));
                return $resultRedirect->setPath('*/*/resolve', ['request_id' => $request->getId(), '_current' => true]);
            }
            try {
                $request->setStatus($status->getId());

                $this->_eventManager->dispatch(
                    'rma_request_resolve_before',
                    ['rma' => $request]
                );

                $request->save();
                $request->saveStatusHistoryObject("admin");
                $additionInfomation = array(
                    "template_customer" =>  $data['template_vendor'],
                    "template_vendor" => $data['template_vendor'],
                    "custom_message_customer" =>  $data['custom-message-customer'],
                    "custom_message_vendor" => $data['custom-message-vendor'],
                );
                $request->processMarkResolve($additionInfomation);

                $this->_eventManager->dispatch(
                    'rma_request_resolve_after',
                    ['rma' => $request,"addition_information" =>$additionInfomation]
                );

                if($request->getVendorObject()->getId()){
                    $this->_eventManager->dispatch(
                        'vnecoms_vendors_push_notification',
                        [
                            'vendor_id' => $request->getVendorObject()->getId(),
                            'type' => 'rma',
                            'message' => __('RMA #%1 has been changed it\'s status to %2'
                                ,'<strong>'.$request->getIncrementId().'</strong>',
                                $request->getStatusTitle()
                            ),
                            'additional_info' => ['id' => $request->getId()],
                        ]
                    );
                }


                $this->messageManager->addSuccess(__('Rma was successfully processed'));
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
