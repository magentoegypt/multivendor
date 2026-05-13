<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsRMA\Controller\Vendors\Request;

use Vnecoms\Vendors\Controller\Vendors\Action;
use Vnecoms\Vendors\App\Action\Context;
use Magento\Cms\Controller\Adminhtml\Page\PostDataProcessor;
use Vnecoms\VendorsRMA\Model\Source\Email\Type as EMAIL_TYPE;
use Vnecoms\VendorsRMA\Model\Source\Message\Type as MESSAGE_TYPE;

class SaveEscalate extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_Vendors::rma_request';
    /**
     * @var \Vnecoms\VendorsRMA\Model\RequestFactory
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
     * @var RmaViewAuthorizationInterface
     */
    protected $rmaAuthorization;

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
        \Vnecoms\VendorsRMA\Model\RequestFactory $requestFactory,
        \Vnecoms\RMA\Helper\Config $helperTicket,
        \Vnecoms\RMA\Model\StatusFactory $status,
        RmaViewAuthorizationInterface $rmaAuthorization
    )
    {
        $this->rmaAuthorization = $rmaAuthorization;
        $this->dataProcessor = $dataProcessor;
        $this->requestFactory = $requestFactory;
        $this->_helperRequest = $helperTicket;
        $this->status        = $status;
        parent::__construct($context);
    }


    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {

        $data = $this->getRequest()->getPostValue("request");
        $attachment = $this->getRequest()->getPostValue("file_upload");
        if($attachment){
            $data["attachment"] = $this->_filterTicketPostData($attachment);
        }else{
            $data["attachment"] = null;
        }
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data["request_id"]) {
            $data = $this->dataProcessor->filter($data);
            $request  = $this->requestFactory->create();;
            if ($data["request_id"]) {
                $request->load($data["request_id"]);
            }
            if (!$request->getId() || !$this->rmaAuthorization->canView($request) || $request->getEscalateObject(true)->getId()) {
                $this->messageManager->addError(__('This RMA no longer exists.'));
                $this->_redirect('*/*');
                return;
            }

            // $ticket->setData($data);
            $this->_eventManager->dispatch(
                'rma_request_prepare_escalate',
                ['rma' => $request, 'request' => $this->getRequest()]
            );
            if (!$this->dataProcessor->validate($data)) {
                return $resultRedirect->setPath('*/*/view', ['request_id' => $request->getId(), '_current' => true]);
            }
            if($request->getState() == \Vnecoms\VendorsRMA\Model\Request::STATE_AWAITING){
                $status = $this->status->create()->load(\Vnecoms\VendorsRMA\Model\Request::STATUS_BEING,"code");
            }else{
                $status = $this->status->create()->load(\Vnecoms\VendorsRMA\Model\Request::STATUS_AWAITING,"code");
            }
            $this->_eventManager->dispatch(
                'rma_request_prepare_save',
                ['rma' => $request, 'request' => $this->getRequest()]
            );
            try {
                if(!$data["message"]){
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('Something went wrong while saving the escalate(s).')
                    );
                    return false;
                }

                $request->setStatus($status->getId());
                $request->save();
                $additionInfomation = array(
                    "message" =>  $this->_helperRequest->converText($data['message']),
                    'attachment'=> $data['attachment'],
                    'type'=> MESSAGE_TYPE::TYPE_REPLY_VENDOR,
                );
                $request->saveEscalateObject($additionInfomation);
                $request->saveStatusHistoryObject("vendor");
                $this->_eventManager->dispatch(
                    'rma_request_escalate_after',
                    ['rma' => $request,"addition_information" =>$additionInfomation]
                );
                $this->messageManager->addSuccess(__('The request has been escalated'));
                return $resultRedirect->setPath('*/*/view',["request_id"=>$request->getId()]);
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


    /**
     * Filter ticket data
     *
     * @param array $rawData
     * @return array
     */
    protected function _filterTicketPostData(array $attachments)
    {
        $attachmentText = null;
        // @todo It is a workaround to prevent saving this data in category model and it has to be refactored in future

        if (isset($attachments) && is_array($attachments)) {

            foreach($attachments as $key=>$attachment){
                if (isset($attachment) && isset($attachment)) {
                    $attachmentText .= $attachment.",";
                } else {
                    unset($attachments[$key]);
                }
            }

        }
        $attachments = trim($attachmentText,",");
        return $attachments;
    }
}
