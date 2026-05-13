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

class UpdateAmount extends \Vnecoms\VendorsRMA\Controller\Vendors\Vendors
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

    protected $_helperConfig;

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
        \Vnecoms\RMA\Helper\Config $helper,
        \Vnecoms\RMA\Model\StatusFactory $status,
        RmaViewAuthorizationInterface $rmaAuthorization
    )
    {
        $this->rmaAuthorization = $rmaAuthorization;
        $this->dataProcessor = $dataProcessor;
        $this->requestFactory = $requestFactory;
        $this->_helperConfig = $helper;
        $this->status        = $status;
        parent::__construct($context);
    }
    /**
     * process ajax
     * @return void
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $requestObject  = $this->requestFactory->create()->load($id);

        if (!$requestObject->getId() || !$this->rmaAuthorization->canView($requestObject)) {
            $this->messageManager->addError(__('This RMA no longer exists.'));
            $this->_redirect('*/*');
            return;
        }

        $customerAmountRefund = $requestObject->getRefundAmountObject();
        /**
         * save refund  amount vendor
         */
        $this->_eventManager->dispatch(
            'rma_request_prepare_save',
            ['rma' => $requestObject, 'request' => $this->getRequest()]
        );
        if($requestObject->getStatusObject()->getCode() == \Vnecoms\RMA\Model\Request::STATUS_PENDING){
            $status = $this->status->create()->load(\Vnecoms\RMA\Model\Request::STATUS_APPROVAL,"code");
            $requestObject->setStatus($status->getId());
        }
        $requestObject->save();
        $requestObject->saveAmountRefundObject("custom_amount",$customerAmountRefund->getAmount(), "vendor");
        $requestObject->sendMailAmountRefundChangeNotify();
        $amount = $requestObject->getOrderObject()->formatPrice($customerAmountRefund->getAmount());
        $this->getResponse()->setBody($amount);
    }


}
