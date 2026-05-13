<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Controller\Customer;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Cms\Controller\Adminhtml\Page\PostDataProcessor;
use Vnecoms\RMA\Model\Source\Email\Type as EMAIL_TYPE;
use Vnecoms\RMA\Model\Source\Message\Type as MESSAGE_TYPE;

class Save extends \Magento\Customer\Controller\AbstractAccount
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
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

    protected $_helperConfig;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_session;

    /**
     * Save constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param PostDataProcessor $dataProcessor
     * @param \Magento\Customer\Model\Session $session
     * @param \Vnecoms\RMA\Model\RequestFactory $requestFactory
     * @param \Vnecoms\RMA\Helper\Config $helperConfig
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        PostDataProcessor $dataProcessor,
        \Magento\Customer\Model\Session $session,
        \Vnecoms\RMA\Model\RequestFactory $requestFactory,
        \Vnecoms\RMA\Helper\Config $helperConfig
    ) {
        $this->resultPageFactory    = $resultPageFactory;
        $this->dataProcessor        = $dataProcessor;
        $this->_session             = $session;
        $this->requestFactory       = $requestFactory;
        $this->_helperConfig        = $helperConfig;
        parent::__construct($context);
    }

    /**
     * Customer order history
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();

        $attachment = $this->getRequest()->getPostValue("file_upload");
        if ($attachment) {
            $data["attachment"] = $this->_filterRequestPostData($attachment);
        } else {
            $data["attachment"] = null;
        }
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($data) {
            $typeReply = MESSAGE_TYPE::TYPE_REPLY_CUSTOMER;
            $type_send_mail = EMAIL_TYPE::TYPE_REPLY_CUSTOMER;
            $to = $this->_helperConfig->contactsName() ? $this->_helperConfig->contactsName() : __("Administrator");

            if(!isset($data['orderitems'])){
                $this->messageManager->addError(__("You must select item RMA"));
                return $resultRedirect->setPath('*/*/new', ['_current' => true]);
            }

            foreach ($data['orderitems'] as $key => $value) {
                $data['order_item_id'][] = ['item_id'=>$key,'item_qty' => $data[$key]];
            }
            if (empty($data['entity_id'])) {
                $data['entity_id'] = null;
                $order = $this->_objectManager->create('Magento\Sales\Model\Order')
                    ->loadByIncrementId($data['order_incremental_id']);
                if ($order->getId()) {
                    $customer= $this->_objectManager->create('Magento\Customer\Model\Customer')
                        ->load($order->getCustomerId());
                    if (!$order->getCustomerId() || !$customer->getId()) {
                        $customer_name = $order->getBillingAddress()->getFirstname()." ".
                            $order->getBillingAddress()->getLastname();
                    } else {
                        $customer_name = $customer->getName();
                        $data["customer_id"] = $order->getCustomerId();
                    }
                    $from = $data["customer_name"] = $customer_name;
                    $data["customer_email"] = $order->getCustomerEmail();
                }
            }

            $data = $this->dataProcessor->filter($data);
            $data["ip_address"] = $this->_helperConfig->getClientIP();
            $data["status"] = 1;
            $request  = $this->requestFactory->create();
            ;

            $request->setData($data);

            $this->_eventManager->dispatch(
                'rma_request_prepare_save',
                ['rma' => $request, 'request' => $this->getRequest()]
            );
            if (!$this->dataProcessor->validate($data)) {
                return $resultRedirect->setPath('*/*/new', ['entity_id' => $request->getId(), '_current' => true]);
            }

            try {
                $object_manager = \Magento\Framework\App\ObjectManager::getInstance();

                $secret = $this->_helperConfig->secretKey();
                if ($this->getRequest()->getPostValue('g-recaptcha-response')) {
                    $recaptcha = new \ReCaptcha\ReCaptcha($secret);
                    $resp = $recaptcha->verify($this->getRequest()->getPostValue('g-recaptcha-response'), $_SERVER['REMOTE_ADDR']);
                    if (!$resp->isSuccess()) {
                        $error = true;
                        foreach ($resp->getErrorCodes() as $code) {
                            $this->_session->setRequestData($data);
                            $this->messageManager->addError(__('You saved request #'));

                            return $resultRedirect->setPath('*/*/new');
                        }
                    }
                }
                $errors = $request->validateItems($data["order_item_id"]);
                if ($errors !== true) {
                    throw new \Exception(implode("<br />", $errors));
                }

                $errors = $request->validate();
                if ($errors !== true) {
                    throw new \Exception(implode("<br />", $errors));
                }
                $request->save();
                /** save address for request */
                $request->saveAddressObject();
                /** save history status for request */
                $request->saveStatusHistoryObject(false);

                /** @var save message object $additionInfomation */
                $additionInfomation = [
                    "message"       =>  $this->_helperConfig->converText($data['message']),
                    'attachment'    => $data['attachment'],
                    'type_reply'    => $typeReply,
                    'type_send_mail'=>$type_send_mail,
                    'from'          =>$from,
                    'to'            =>$to,
                    'isEdit'        => false,
                    'isClosed'      => false
                ];
                $request->saveMessageObject($additionInfomation);
                /** save Item Object */
                $request->saveItemsObject($data["order_item_id"]);

                $this->_eventManager->dispatch(
                    'rma_request_save_after',
                    ['request' => $request,"addition_information" =>$additionInfomation]
                );

                $this->messageManager->addSuccess(__('You saved request #').$request->getIncrementId());
                $this->_session->setRequestData(false);
                return $resultRedirect->setPath('*/*/');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while saving the page.'));
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
        $attachments = $attachmentText ? trim($attachmentText, ",") : null;
        return $attachments;
    }
}
