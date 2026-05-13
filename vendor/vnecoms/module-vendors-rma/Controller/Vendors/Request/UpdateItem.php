<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsRMA\Controller\Vendors\Request;


use Vnecoms\Vendors\Controller\Vendors\Action;
use Vnecoms\Vendors\App\Action\Context;


class UpdateItem extends Action
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
     * @var \Vnecoms\RMA\Model\ItemFactory
     */

    protected $itemFactory;

    /**
     * UpdateItem constructor.
     * @param Context $context
     * @param \Vnecoms\RMA\Model\ItemFactory $itemFactory
     * @param \Vnecoms\VendorsRMA\Model\RequestFactory $requestFactory
     */
    public function __construct(
        Context $context,
        \Vnecoms\RMA\Model\ItemFactory $itemFactory,
        \Vnecoms\VendorsRMA\Model\RequestFactory $requestFactory
    )
    {
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

            $reponse ["error"] = false;
            $reponse ["html"] = $value;
        } catch (\Exception $e) {
            $reponse ["error"] = true;
            $reponse ["message"] = $e->getMessage();
        }
        $this->getResponse()->setBody(json_encode($reponse));
    }


}