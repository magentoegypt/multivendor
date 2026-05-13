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

class Reply extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_Vendors::rma_request';
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
     * @var RmaViewAuthorizationInterface
     */
    protected $rmaAuthorization;

    /**
     * @param Context $context
     * @param PostDataProcessor $dataProcessor
     */
    public function __construct(
        Context $context,
        PostDataProcessor $dataProcessor ,
        \Vnecoms\RMA\Model\RequestFactory $requestFactory,
        \Vnecoms\RMA\Helper\Config $helperTicket,
        RmaViewAuthorizationInterface $rmaAuthorization
    )
    {
        $this->rmaAuthorization = $rmaAuthorization;
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
                $typeReply = MESSAGE_TYPE::TYPE_REPLY_VENDOR;
                $from = $request->getVendorObject()->getName() ? $request->getVendorObject()->getName() : __("Vendor");
                $to = $request->getCustomerName();
                $type_send_mail= EMAIL_TYPE::TYPE_REPLY_DEPARMENT;

            }

            if (!$request->getId() || !$this->rmaAuthorization->canView($request)) {
                $this->messageManager->addError(__('This RMA no longer exists.'));
                $this->_redirect('*/*');
                return;
            }

            // $ticket->setData($data);
            $this->_eventManager->dispatch(
                'rma_request_prepare_save',
                ['rma' => $request, 'request' => $this->getRequest()]
            );
            if (!$this->dataProcessor->validate($data)) {
                return $resultRedirect->setPath('*/*/view', ['request_id' => $request->getId(), '_current' => true]);
            }

            try {
                $request->save();
                $additionInfomation = array(
                    "message" =>  $this->_helperRequest->converText($data['message']),
                    'attachment'=> $data['attachment'],
                    'type_reply'=> $typeReply,
                    'type_send_mail'=>$type_send_mail,
                    'from'=>$from,
                    'to'=>$to,
                    'isEdit' => true,
                );
                $request->saveMessageObject($additionInfomation);

                $this->_eventManager->dispatch(
                    'rma_request_reply_after',
                    ['rma' => $request,"addition_information" =>$additionInfomation]
                );
                
                $block = $this->_view->getLayout()
                    ->createBlock('Vnecoms\RMA\Block\Adminhtml\Request\Edit\AjaxMessage')
                    ->setTemplate('Vnecoms_VendorsRMA::request/message/list.phtml')->setRma($request);

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
