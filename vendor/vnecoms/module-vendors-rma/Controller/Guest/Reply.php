<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsRMA\Controller\Guest;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Action\Context;
use Vnecoms\RMA\Model\Source\Email\Type as EMAIL_TYPE;
use Vnecoms\RMA\Model\Source\Message\Type as MESSAGE_TYPE;

class Reply extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Vnecoms\RMA\Model\RequestFactory
     */
    protected $requestFactory;

    /**
     * @var \Vnecoms\RMA\Helper\Config
     */

    protected $_helperRequest;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     *    \Magento\Customer\Model\Session $session,
     */
    protected $_customerSession;

    /**
     * @var RmaViewAuthorizationInterface
     */
    protected $rmaAuthorization;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;

    public function __construct(
        Context $context,
        \Vnecoms\RMA\Model\RequestFactory $requestFactory,
        \Vnecoms\RMA\Helper\Config $helperRequest,
        \Magento\Customer\Model\Session $session,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Vnecoms\RMA\Controller\Guest\RmaViewAuthorizationInterface $rmaAuthorization
    )
    {
        $this->rmaAuthorization   = $rmaAuthorization;
        $this->requestFactory = $requestFactory;
        $this->_helperRequest = $helperRequest;
        $this->_customerSession =  $session;
        $this->_storeManager = $storeManager;
        $this->_messageManager = $context->getMessageManager();
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
        $request_id = $data["request_id"];
        unset($data["request_id"]);

        $attachment = $this->getRequest()->getPostValue("file_upload");
        if($attachment){
            $data["attachment"] = $this->_filterRequestPostData($attachment);
        }else{
            $data["attachment"] = null;
        }
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if (!$this->_customerSession->getPostRma()){
            return $resultRedirect->setPath('rma/guest/list');
        }

        if ($data) {
            $typeReply = MESSAGE_TYPE::TYPE_REPLY_CUSTOMER;
            $request  = $this->requestFactory->create();;
            $request->load($request_id);

            if (!$request->getId() || !$this->rmaAuthorization->canView($request)) {
                $this->messageManager->addError(__('This RMA no longer exists.'));
                return $resultRedirect->setPath('rma/guest/list');
            }

            $from = $request->getData('customer_name');
            $to =   $request->getVendorObject()->getName() ? $request->getVendorObject()->getName() : $this->_helperRequest->contactsName();

            $type_send_mail= EMAIL_TYPE::TYPE_REPLY_CUSTOMER;
            // $ticket->setData($data);
            $this->_eventManager->dispatch(
                'rma_request_prepare_save',
                ['rma' => $request, 'request' => $this->getRequest()]
            );

            try {
                $request->save();
                $additionInfomation = array(
                    "message" =>  $this->_helperRequest->converText($data['message']),
                    'attachment'=> $data['attachment'],
                    'type_reply'=> $typeReply,
                    'type_send_mail'=>$type_send_mail,
                    'from'=>$from,
                    'to'=>$to,
                    'isEdit' => false
                );
                $request->saveMessageObject($additionInfomation);

                $this->_eventManager->dispatch(
                    'rma_request_reply_after',
                    ['rma' => $request,"addition_information" =>$additionInfomation]
                );

                if($request->getVendorObject()->getId()){
                    $this->_eventManager->dispatch(
                        'vnecoms_vendors_push_notification',
                        [
                            'vendor_id' => $request->getVendorObject()->getId(),
                            'type' => 'rma',
                            'message' => __('RMA #%1 has a new message','<strong>'.$request->getIncrementId().'</strong>'),
                            'additional_info' => ['id' => $request->getId()],
                        ]
                    );
                }


                $block = $this->_view->getLayout()
                    ->createBlock('Vnecoms\RMA\Block\Frontend\View\Message')
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
     * Filter ticket data
     *
     * @param array $rawData
     * @return array
     */
    protected function _filterRequestPostData(array $attachments)
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
