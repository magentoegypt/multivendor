<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/21/2016
 * Time: 05:01 PM
 */
namespace Vnecoms\VendorsRMA\Controller\Adminhtml\Request;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Cms\Controller\Adminhtml\Page\PostDataProcessor;
use Magento\Framework\App\Request\DataPersistorInterface;
use Vnecoms\VendorsRMA\Model\Source\Email\Type as EMAIL_TYPE;
use Vnecoms\VendorsRMA\Model\Source\Message\Type as MESSAGE_TYPE;

class Save extends Action
{
    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;
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
     * @param Context $context
     * @param PostDataProcessor $dataProcessor
     */
    public function __construct(
        Context $context,
        PostDataProcessor $dataProcessor ,
        \Vnecoms\VendorsRMA\Model\RequestFactory $requestFactory,
        \Vnecoms\RMA\Helper\Config $helperConfig,
        DataPersistorInterface $dataPersistor
    )
    {
        $this->dataProcessor = $dataProcessor;
        $this->dataPersistor = $dataPersistor;
        $this->requestFactory = $requestFactory;
        $this->_helperConfig = $helperConfig;
        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Vnecoms_RMA::save');
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {

        $data = $this->getRequest()->getPostValue();
        $data = $this->attachmentPreprocessing($data);
        $data = $this->_filterRequestPostData($data);
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($data) {
            $typeReply = MESSAGE_TYPE::TYPE_REPLY_DEPARMENT;
            $type_send_mail = EMAIL_TYPE::TYPE_REPLY_DEPARMENT;
            $from = $this->_helperConfig->contactsName() ? $this->_helperConfig->contactsName() : __("Administrator");

            if (empty($data['entity_id'])) {
                $data['entity_id'] = null;
                $order = $this->_objectManager->create('Magento\Sales\Model\Order')
                    ->loadByIncrementId($data['order_incremental_id']);
                if($order->getId()){
                    $customer= $this->_objectManager->create('Magento\Customer\Model\Customer')
                        ->load($order->getCustomerId());
                    if(!$order->getCustomerId() || !$customer->getId()){
                        $customer_name = $order->getBillingAddress()->getFirstname()." ".
                            $order->getBillingAddress()->getLastname();
                    }
                    else{
                        $customer_name = $customer->getName();
                        $data["customer_id"] = $order->getCustomerId();
                    }
                    $to = $data["customer_name"] = $customer_name;
                    $data["customer_email"] = $order->getCustomerEmail();
                }

            }

            $data = $this->dataProcessor->filter($data);
            $data["ip_address"] = $this->_helperConfig->getClientIP();
            $request  = $this->requestFactory->create();;

            $id = $this->getRequest()->getParam('entity_id');
            if ($id) {
                $request->load($id);
                $to = $request->getCustomerName();
            }
            $request->setData($data);

            $this->_eventManager->dispatch(
                'helpdesk_request_prepare_save',
                ['rma' => $request, 'request' => $this->getRequest()]
            );
            if (!$this->dataProcessor->validate($data)) {
                return $resultRedirect->setPath('*/*/edit', ['entity_id' => $request->getId(), '_current' => true]);
            }

            try {
                //var_dump($data["order_item_id"]);exit;
                $errors = $request->validateItems($data["order_item_id"]);
                if($errors !== true){
                    throw new \Exception(implode("<br />", $errors));
                }

                $errors = $request->validate();
                if($errors !== true){
                    throw new \Exception(implode("<br />", $errors));
                }

                $request->save();

                /** save address for request */
                $request->saveAddressObject();
                /** @var save message object $additionInfomation */
                $additionInfomation = array(
                    "message" =>  $this->_helperConfig->converText($data['message']),
                    'attachment'=> $data['attachment'],
                    'type_reply'=> $typeReply,
                    'type_send_mail'=>$type_send_mail,
                    'from'=>$from,
                    'to'=>$to,
                    'isEdit' => false,
                    'isClosed' => false
                );
                $request->saveMessageObject($additionInfomation);
                /** save Item Object */
                $request->saveItemsObject($data["order_item_id"]);

                /** save history status for request */
                $request->saveStatusHistoryObject("admin");


                /** save refund amount */
                $request->saveAmountRefundObject($data["refund_amount_type"],$data["refund_custom_amount"], "admin");


                $this->_eventManager->dispatch(
                    'rma_request_save_after',
                    ['request' => $request,"addition_information" =>$additionInfomation]
                );

                if($request->getVendorObject()->getId()){
                    $this->_eventManager->dispatch(
                        'vnecoms_vendors_push_notification',
                        [
                            'vendor_id' => $request->getVendorObject()->getId(),
                            'type' => 'rma',
                            'message' => __('A new RMA #%1 has been submited','<strong>'.$request->getIncrementId().'</strong>'),
                            'additional_info' => ['id' => $request->getId()],
                        ]
                    );
                }

                $this->messageManager->addSuccess(__('You saved request #').$request->getIncrementId());
                $this->dataPersistor->clear('rma_request');
                return $resultRedirect->setPath('*/*/');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }

            $this->dataPersistor->set('rma_request', $data);
            return $resultRedirect->setPath('*/*/new');
        }
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Attachment data preprocessing
     *
     * @param array $data
     *
     * @return array
     */
    public function attachmentPreprocessing($data)
    {
        if (empty($data['attachment'])) {
            unset($data['attachment']);
            $data['attachment']['delete'] = true;
        }
        return $data;
    }


    /**
     * Filter request data
     *
     * @param array $rawData
     * @return array
     */
    protected function _filterRequestPostData(array $rawData)
    {
        $data = $rawData;
        $attachmentText = null;
        // @todo It is a workaround to prevent saving this data in category model and it has to be refactored in future
        if (isset($data['attachment']) && is_array($data['attachment'])) {

            foreach($data['attachment'] as $key=>$attachment){
                if (!empty($attachment['delete'])) {
                    $data['attachment'][$key] = null;
                } else {
                    if (isset($attachment['name']) && isset($attachment['tmp_name'])) {
                        $attachmentText .= $attachment['name'].",";
                    } else {
                        unset($data['attachment'][$key]);
                    }
                }
            }

        }
        $data['attachment'] = $attachmentText ? trim($attachmentText,",") : null;
        return $data;
    }
}
