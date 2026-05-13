<?php

namespace Vnecoms\Quotation\Observer;

use Magento\Framework\Event\ObserverInterface;

class UpdateQuoteItemPrice implements ObserverInterface
{
    /**
     * @var \Vnecoms\Quotation\Model\ProposalFactory
     */
    protected $proposalFactory;
    
    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * UpdateQuoteItemPrice constructor.
     * @param \Vnecoms\Quotation\Model\ProposalFactory $proposalFactory
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        \Vnecoms\Quotation\Model\ProposalFactory $proposalFactory,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
    ){
        $this->proposalFactory = $proposalFactory;
        $this->priceCurrency = $priceCurrency;
    }
    
    /**
     * Update free gift quanty
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote\Item */
        $quoteItem = $observer->getQuoteItem();
        /** @var \Magento\Catalog\Model\Product */
        $product = $observer->getProduct();        
        if($proposalId = $product->getCustomOption('quotation_proposal_id')){
            $proposalId = $proposalId->getValue();
            $proposal = $this->proposalFactory->create()->load($proposalId);
            if($proposalId){
                $quoteItem->setOriginalCustomPrice($this->priceCurrency->convert($proposal->getBasePrice()));
                $quoteItem->setCustomPrice($this->priceCurrency->convert($proposal->getBasePrice()));
            }
        }      
    }
}
