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

class UpdateItem extends Action
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
     * @var \Vnecoms\RMA\Model\ItemFactory
     */

    protected $itemFactory;

    /**
     * UpdateItem constructor.
     * @param Context $context
     * @param \Vnecoms\RMA\Model\ItemFactory $itemFactory
     * @param \Vnecoms\RMA\Model\RequestFactory $requestFactory
     */
    public function __construct(
        Context $context,
        \Vnecoms\RMA\Model\ItemFactory $itemFactory,
        \Vnecoms\RMA\Model\RequestFactory $requestFactory
    ) {
    
        $this->itemFactory = $itemFactory;
        $this->requestFactory = $requestFactory;
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

            $response ["error"] = false;
            $reponse ["html"] = $value;
        } catch (\Exception $e) {
            $reponse ["error"] = true;
            $reponse ["message"] = $e->getMessage();
        }
        $this->getResponse()->setBody(json_encode($reponse));
    }
}
