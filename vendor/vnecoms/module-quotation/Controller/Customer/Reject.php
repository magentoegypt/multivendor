<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 * See LICENSE.txt for license details.
 */


namespace Vnecoms\Quotation\Controller\Customer;

use Magento\Framework\Exception\NoSuchEntityException;
use Vnecoms\Quotation\Model\QuoteRepository;
use Vnecoms\Quotation\Model\Quote;
use Magento\Framework\App\Action\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart as CustomerCart;

class Reject extends \Magento\Framework\App\Action\Action
{
    /**
     * @var QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;
    
    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;
    
    /**
     * @param Context $context
     * @param QuoteRepository $quoteRepository
     * @param \Magento\Customer\Model\Session $customerSession
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        Context $context,
        QuoteRepository $quoteRepository,
        \Magento\Customer\Model\Session $customerSession,
        ProductRepositoryInterface $productRepository,
        CustomerCart $cart
    ) {
        parent::__construct($context);
        $this->quoteRepository = $quoteRepository;
        $this->customerSession = $customerSession;
        $this->productRepository = $productRepository;
        $this->cart = $cart;
    }

    /**
     * Initialize product instance from request data
     *
     * @return \Magento\Catalog\Model\Product|false
     */
    protected function _initProduct($productId)
    {
        if ($productId) {
            $storeId = $this->_objectManager->get(
                \Magento\Store\Model\StoreManagerInterface::class
            )->getStore()->getId();
            try {
                return $this->productRepository->getById($productId, false, $storeId);
            } catch (NoSuchEntityException $e) {
                return false;
            }
        }
        return false;
    }
    
    
    public function execute()
    {
        try{
            $quoteId = $this->getRequest()->getParam('quote_id',false);
            $quote = $this->quoteRepository->getById($quoteId);

            if(
                $this->customerSession->isLoggedIn() &&
                ($quote->getCustomerId() != $this->customerSession->getCustomerId())
            ) {
                throw new NoSuchEntityException(__("The quote is not available."));
            }elseif(
                !$this->customerSession->isLoggedIn() &&
                $quote->getId() != $this->customerSession->getData(\Vnecoms\Quotation\Helper\Guest::QUOTATION_GUEST_KEY)
            ){
                throw new NoSuchEntityException(__("The quote is not available."));
            }

            if($quote->getStatus() != Quote::STATUS_SENT){
                throw new NoSuchEntityException(__("The quote status is not valid."));
            }

            $this->quoteRepository->reject($quote);

            $this->messageManager->addSuccess(__('The proposal is rejected'));
            if($this->customerSession->isLoggedIn()){
                return $this->_redirect('quotation/customer/view', ['quote_id' => $quote->getId()]);
            }

            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());
            return $resultRedirect;

        } catch (NoSuchEntityException $e) {
            $this->messageManager->addError(__("The quote is not available."));
            if(!$this->customerSession->isLoggedIn()) return $this->_redirect('quotation/guest');
            return $this->_redirect('quotation/customer');
        }catch(\Exception $e){
            $this->messageManager->addError($e->getMessage());
            if(!$this->customerSession->isLoggedIn()) return $this->_redirect('quotation/guest');
            return $this->_redirect('quotation/customer');
        }
    }
}
