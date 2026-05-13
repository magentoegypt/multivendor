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

class Update extends Action
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

    protected $_helperConfig;

    /**
     * @param Context $context
     * @param PostDataProcessor $dataProcessor
     */
    public function __construct(
        Context $context,
        PostDataProcessor $dataProcessor,
        \Vnecoms\RMA\Model\RequestFactory $requestFactory,
        \Vnecoms\RMA\Helper\Config $helper
    ) {

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
        $requestObject->setData($node, $value);

        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $dateObj = $object_manager->get('\Magento\Framework\Stdlib\DateTime\DateTime');

        switch ($node) {
            case "refund_amount":
                $requestObject->saveAmountRefundObject("custom_amount",$value, "admin");
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
        }
        $requestObject->save();
        if (is_array($html)) {
            $html = json_encode($html);
        }
        $this->getResponse()->setBody($html);
    }
}
