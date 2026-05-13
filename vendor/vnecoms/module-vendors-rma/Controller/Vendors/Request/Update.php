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

class Update extends \Vnecoms\VendorsRMA\Controller\Vendors\Vendors
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
     * @param Context $context
     * @param PostDataProcessor $dataProcessor
     */
    public function __construct(
        Context $context,
        PostDataProcessor $dataProcessor ,
        \Vnecoms\VendorsRMA\Model\RequestFactory $requestFactory,
        \Vnecoms\RMA\Helper\Config $helper,
        RmaViewAuthorizationInterface $rmaAuthorization
    )
    {
        $this->rmaAuthorization = $rmaAuthorization;
        $this->dataProcessor = $dataProcessor;
        $this->requestFactory = $requestFactory;
        $this->_helperConfig = $helper;
        parent::__construct($context);
    }
    /**
     * process ajax
     * @return void
     */
    public function execute()
    {
        $node = $this->getRequest()->getParam('node');
        $id = $this->getRequest()->getParam('id');
        $value = $this->getRequest()->getParam('value');
        $requestObject  = $this->requestFactory->create()->load($id);
        if (!$requestObject->getId() || !$this->rmaAuthorization->canView($requestObject)) {
            $this->messageManager->addError(__('This RMA no longer exists.'));
            $this->_redirect('*/*');
            return;
        }
        $requestObject->setData($node,$value);
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $dateObj = $object_manager->get('\Magento\Framework\Stdlib\DateTime\DateTime');
        switch ($node){

            case "refund_amount":
                $requestObject->saveAmountRefundObject("custom_amount",$value, "vendor");
                $requestObject->sendMailAmountRefundChangeNotify();

                $block = $this->_view->getLayout()
                    ->createBlock('Vnecoms\VendorsRMA\Block\Vendor\Request\Edit\Amount')
                    ->setTemplate('Vnecoms_VendorsRMA::request/amount/list.phtml')->setRma($requestObject);

                $html = [
                    'amount_list' => $block->toHtml(),
                    'amount_html' => $requestObject->getOrderObject()->formatPrice($value),
                    'amount' => $value
                ];
                break;
            default:
                $html = $value;
                break;
        }
        $requestObject->save();
        if(is_array($html)) $html = json_encode($html);
        $this->getResponse()->setBody($html);
    }


}
