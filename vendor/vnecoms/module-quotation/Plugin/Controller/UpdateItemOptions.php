<?php

namespace Vnecoms\Quotation\Plugin\Controller;

use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;

class UpdateItemOptions
{
    protected $request;

    /**
     * @var CustomerCart
     */
    protected $cart;

    /**
     * @var ResultFactory
     */
    protected $resultFactory;

    /**
     * @var MessageManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @param \Magento\Framework\App\RequestInterface $request
     * @param CustomerCart $cart
     * @param ResultFactory $resultFactory
     * @param RedirectFactory $resultRedirectFactory
     * @param MessageManagerInterface $messageManager
     */
    public function __construct
    (
        \Magento\Framework\App\RequestInterface $request,
        CustomerCart $cart,
        ResultFactory $resultFactory,
        RedirectFactory $resultRedirectFactory,
        MessageManagerInterface $messageManager
    )
    {
        $this->request = $request;
        $this->cart = $cart;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->resultFactory = $resultFactory;
        $this->messageManager =$messageManager;
    }

    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Remove qty fields
     * @param \Magento\Catalog\Helper\Product\Composite $composite
     * @param $result
     * @return mixed
     */
    public function afterExecute(
        \Magento\Checkout\Controller\Cart\Configure $cart,
        $result
    )
    {
        $id = (int)$this->getRequest()->getParam('id');

        $quoteItem = $this->cart->getQuote()->getItemById($id);

        if($quoteItem->getOptionByCode('quotation_proposal_id')){
            $this->messageManager->addErrorMessage(__("The item '%1' is related to the quotation. It's not allowed to change the qty.",$quoteItem->getName(), $this->cart->getQuote()->getIncrementId()));
           // throw new \Magento\Framework\Exception\LocalizedException(__("The item '%1' is related to the quote #%2. It's not allowed to change the qty.",$quoteItem->getName(), $this->cart->getQuote()->getIncrementId()));
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath("checkout/cart");
            return $resultRedirect;
        }

        return $result;
    }
}
