<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsRMA\Controller\Customer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Action\Context;
use Vnecoms\VendorsRMA\Model\Source\Email\Type as EMAIL_TYPE;
use Vnecoms\VendorsRMA\Model\Source\Message\Type as MESSAGE_TYPE;

class UpdateEscalate extends \Magento\Customer\Controller\AbstractAccount
{
    /**
     * @var  \Vnecoms\VendorsRMA\Model\Request\EscalateFactory
     */
    protected $escalateFactory;

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
     * UpdateEscalate constructor.
     * @param Context $context
     * @param \Vnecoms\RMA\Model\RequestFactory $requestFactory
     * @param \Vnecoms\VendorsRMA\Model\Request\EscalateFactory $escalateFactory
     * @param \Vnecoms\RMA\Helper\Config $helperTicket
     * @param \Vnecoms\RMA\Controller\Customer\RmaViewAuthorizationInterface $rmaAuthorization
     */
    public function __construct(
        Context $context,
        \Vnecoms\RMA\Model\RequestFactory $requestFactory,
        \Vnecoms\VendorsRMA\Model\Request\EscalateFactory $escalateFactory,
        \Vnecoms\RMA\Helper\Config $helperTicket,
        \Vnecoms\RMA\Controller\Customer\RmaViewAuthorizationInterface $rmaAuthorization
    ) {
        $this->escalateFactory = $escalateFactory;
        $this->rmaAuthorization = $rmaAuthorization;
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
        $result = [];
        $requestId = $this->getRequest()->getPostValue("request_id");
        $data = $this->getRequest()->getPostValue("request");
        $attachment = $this->getRequest()->getPostValue("file_upload");

        if ($requestId) {
            $data["request_id"] = $requestId;
        }

        if($attachment){
            $data["attachment"] = $this->_filterTicketPostData($attachment);
        }else{
            $data["attachment"] = null;
        }
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if (isset($data["request_id"])) {
            $request  = $this->requestFactory->create();

            if ($data["request_id"]) {
                $request->load($data["request_id"]);
            }

            if (!$request->getId() || !$this->rmaAuthorization->canView($request)) {
                $this->messageManager->addError(__('This RMA no longer exists.'));
                $this->_redirect('*/*');
                return;
            }
            $this->_eventManager->dispatch(
                'rma_request_prepare_save',
                ['rma' => $request, 'request' => $this->getRequest()]
            );
            $escalate =   $this->escalateFactory->create()->getCollection()
                ->addFieldToFilter("request_id",$request->getId())
                ->addFieldToFilter("type",MESSAGE_TYPE::TYPE_REPLY_CUSTOMER)->getFirstItem();
            try {
                $request->save();
                $escalate->updateEscalateAttachment($data['attachment']);
                $escalate->save();

                $block = $this->_view->getLayout()
                    ->createBlock('Vnecoms\VendorsRMA\Block\Frontend\View\Escalate')
                    ->setTemplate('Vnecoms_VendorsRMA::request/escalate/list.phtml')->setEscalate($escalate);

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
