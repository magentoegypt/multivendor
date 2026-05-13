<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Controller\Customer;

use Vnecoms\RMA\Controller\IndexInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\Filter\Date;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;

/**
 * Class Upload
 */
class UpdateItem extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Vnecoms\RMA\Model\RequestFactory
     */
    protected $requestFactory;


    /**
     * @var \Vnecoms\RMA\Helper\Config
     */

    protected $_helperConfig;

    /**
     * @var \Vnecoms\RMA\Model\ItemFactory
     */
    protected $itemFactory;

    /**
     * UpdateItem constructor.
     * @param Context $context
     * @param \Vnecoms\RMA\Model\ItemFactory $itemFactory
     * @param \Vnecoms\RMA\Model\RequestFactory $requestFactory
     * @param \Vnecoms\RMA\Helper\Config $helper
     */
    public function __construct(
        Context $context,
        \Vnecoms\RMA\Model\ItemFactory $itemFactory,
        \Vnecoms\RMA\Model\RequestFactory $requestFactory,
        \Vnecoms\RMA\Helper\Config $helper
    ) {
    
        $this->itemFactory = $itemFactory;
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
        $itemId = $this->getRequest()->getParam('id');
        $value = $this->getRequest()->getParam('item_qty');
        $itemObject = $this->itemFactory->create()->load($itemId);
        $data["item_id"] = $itemObject->getOrderItemId();
        $data["item_qty"] = $value;
        $request  = $this->requestFactory->create()->load($itemObject->getRequestId());
        $errors = $request->validateItems([$data]);
        try {
            if ($errors !== true) {
                throw new \Exception(implode("<br />", $errors));
            }
            $itemObject->setQty($value);
            $itemObject->save();

            $reponse ["error"] = false;
            $reponse ["html"] = $value;
        } catch (\Exception $e) {
            $reponse ["error"] = true;
            $reponse ["message"] = $e->getMessage();
        }
        $this->getResponse()->setBody(json_encode($reponse));
    }
}
