<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsRMA\Controller\Guest;

use Magento\Framework\App\Action\Context;
use Vnecoms\VendorsRMA\Model\Source\Message\Type as MESSAGE_TYPE;

class SaveEscalate extends \Magento\Framework\App\Action\Action
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
     * @var \Vnecoms\RMA\Model\StatusFactory
     */
    protected $status;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;

    /**
     * SaveEscalate constructor.
     * @param Context $context
     * @param \Vnecoms\RMA\Model\RequestFactory $requestFactory
     * @param \Vnecoms\RMA\Helper\Config $helperRequest
     * @param \Magento\Customer\Model\Session $session
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Vnecoms\RMA\Model\StatusFactory $status
     * @param \Vnecoms\RMA\Controller\Guest\RmaViewAuthorizationInterface $rmaAuthorization
     */
    public function __construct(
        Context $context,
        \Vnecoms\RMA\Model\RequestFactory $requestFactory,
        \Vnecoms\RMA\Helper\Config $helperRequest,
        \Magento\Customer\Model\Session $session,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Vnecoms\RMA\Model\StatusFactory $status,
        \Vnecoms\RMA\Controller\Guest\RmaViewAuthorizationInterface $rmaAuthorization
    )
    {
        $this->status        = $status;
        $this->rmaAuthorization = $rmaAuthorization;
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
            $data["attachment"] = $this->_filterTicketPostData($attachment);
        }else{
            $data["attachment"] = null;
        }
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($data) {
            $request  = $this->requestFactory->create();;
            $request->load($request_id);

            if (!$request->getId() || !$this->rmaAuthorization->canView($request)) {
                $this->messageManager->addError(__('This RMA no longer exists.'));
                $this->_redirect('*/*');
                return;
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
                    'type'=> MESSAGE_TYPE::TYPE_REPLY_CUSTOMER,
                );
                $request->saveEscalateObject($additionInfomation);
                $request->saveStatusHistoryObject();

                $this->_eventManager->dispatch(
                    'rma_request_escalate_after',
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

                $this->messageManager->addSuccess(__('The request has been escalated'));
                return $resultRedirect->setPath('*/*/view',["id"=>$request->getId()]);
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
