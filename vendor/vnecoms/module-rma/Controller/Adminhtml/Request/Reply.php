<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Controller\Adminhtml\Request;

use Vnecoms\RMA\Controller\Adminhtml\Request\Request;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Cms\Controller\Adminhtml\Page\PostDataProcessor;
use Vnecoms\RMA\Model\Source\Email\Type as EMAIL_TYPE;
use Vnecoms\RMA\Model\Source\Message\Type as MESSAGE_TYPE;

class Reply extends Action
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
     * @param Context $context
     * @param PostDataProcessor $dataProcessor
     */
    public function __construct(
        Context $context,
        PostDataProcessor $dataProcessor,
        \Vnecoms\RMA\Model\RequestFactory $requestFactory,
        \Vnecoms\RMA\Helper\Config $helperTicket
    ) {
    
        $this->dataProcessor = $dataProcessor;
        $this->requestFactory = $requestFactory;
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
        $data = $this->getRequest()->getPostValue("request");
        $attachment = $this->getRequest()->getPostValue("file_upload");
        if ($attachment) {
            $data["attachment"] = $this->_filterRequestPostData($attachment);
        } else {
            $data["attachment"] = null;
        }
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data["request_id"]) {
            $data = $this->dataProcessor->filter($data);
            $request  = $this->requestFactory->create();
            ;

            if ($data["request_id"]) {
                $request->load($data["request_id"]);
                $typeReply = MESSAGE_TYPE::TYPE_REPLY_DEPARMENT;
                $from = $this->_helperRequest->contactsName() ? $this->_helperRequest->contactsName() : __("Admin");
                $to = $request->getCustomerName();
                $type_send_mail= EMAIL_TYPE::TYPE_REPLY_DEPARMENT;
            }
            // $ticket->setData($data);
            $this->_eventManager->dispatch(
                'rma_request_prepare_save',
                ['rma' => $request, 'request' => $this->getRequest()]
            );
            if (!$this->dataProcessor->validate($data)) {
                return $resultRedirect->setPath('*/*/edit', ['request_id' => $request->getId(), '_current' => true]);
            }


            try {
                $request->save();
                $additionInfomation = [
                    "message" =>  $this->_helperRequest->converText($data['message']),
                    'attachment'=> $data['attachment'],
                    'type_reply'=> $typeReply,
                    'type_send_mail'=>$type_send_mail,
                    'from'=>$from,
                    'to'=>$to,
                    'isEdit' => true,
                ];
                $request->saveMessageObject($additionInfomation);

                $this->_eventManager->dispatch(
                    'rma_request_reply_after',
                    ['rma' => $request,"addition_information" =>$additionInfomation]
                );
                
                $block = $this->_view->getLayout()
                    ->createBlock('Vnecoms\RMA\Block\Adminhtml\Request\Edit\AjaxMessage')
                    ->setTemplate('Vnecoms_RMA::request/message/list.phtml')->setRma($request);

                $result = [
                    'error' => false,
                    'message_list' => $block->toHtml()
                ];
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $result = [
                    'error' => true,
                    'msg' => $e->getMessage()
                ];
            } catch (\RuntimeException $e) {
                $result = [
                    'error' => true,
                    'msg' => $e->getMessage()
                ];
            } catch (\Exception $e) {
                $result = [
                    'error' => true,
                    'msg' => $e->getMessage()
                ];
            }
        }
        $this->getResponse()->setBody(json_encode($result));
    }


    /**
     * Filter request data
     *
     * @param array $rawData
     * @return array
     */
    protected function _filterRequestPostData(array $attachments)
    {
        $attachmentText = null;
        // @todo It is a workaround to prevent saving this data in category model and it has to be refactored in future

        if (isset($attachments) && is_array($attachments)) {
            foreach ($attachments as $key => $attachment) {
                if (isset($attachment) && isset($attachment)) {
                    $attachmentText .= $attachment.",";
                } else {
                    unset($attachments[$key]);
                }
            }
        }
        $attachments = trim($attachmentText, ",");
        return $attachments;
    }
}
