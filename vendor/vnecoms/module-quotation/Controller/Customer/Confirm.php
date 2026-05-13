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

class Confirm extends \Magento\Framework\App\Action\Action
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
                ($quote->getCustomerId() != $this->customerSession->getCustomerId())
            ) {
                throw new NoSuchEntityException(__("The quote is not available."));
            }

            if($quote->getStatus() != Quote::STATUS_SENT){
                throw new NoSuchEntityException(__("The quote status is not valid."));
            }

            $proposals = $this->getRequest()->getParam('proposals');
            $this->cart->getQuote()->setTotalsCollectedFlag(false);
            foreach($proposals as $itemId => $proposalId){
                $item = $quote->getItemById($itemId);
                $proposal = $item->getProposalById($proposalId);
                $params = $item->getBuyRequest()->getData();
                $params['qty'] = $proposal->getQty();
                if (isset($params['qty'])) {
                    $filter = new \Magento\Framework\Filter\LocalizedToNormalized(
                        ['locale' => $this->_objectManager->get(
                            \Magento\Framework\Locale\ResolverInterface::class
                        )->getLocale()]
                    );
                    $params['qty'] = $filter->filter((string)$params['qty']);
                }

                $product = $this->_initProduct($item->getProductId());
                $product->addCustomOption('quotation_proposal_id', $proposalId);
                $this->cart->addProduct($product, $params);
            }
            $this->cart->save();
            $quote->setStatus(Quote::STATUS_ACCEPTED)->save();
            $this->messageManager->addSuccess(__('Your items are added to cart'));
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());
            return $resultRedirect;
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addError(__("The quote is not available."));
            return $this->_redirect('quotation/customer');
        }catch(\Exception $e){
            $this->messageManager->addError($e->getMessage());
            return $this->_redirect('quotation/customer');
        }
    }
}
