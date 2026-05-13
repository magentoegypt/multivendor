<?php

namespace Vnecoms\Quotation\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\ObjectManager;

class ValidateQuoteItemQty implements ObserverInterface
{
    /**
     * Update free gift quanty
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Checkout\Model\Cart */
        $cart = $observer->getCart();

        $items = [];
        /*Group items by free gift key*/
        foreach($cart->getQuote()->getAllItems() as $item){
            if($proposalId = $item->getOptionByCode('quotation_proposal_id')){
                $proposalId = $proposalId->getValue();
                $proposal = ObjectManager::getInstance()->create('Vnecoms\Quotation\Model\Proposal')->load($proposalId);
                if(
                    $item->getOrigData('qty') && 
                    ($item->getQty() != $item->getOrigData('qty'))
                ) {
                    throw new \Magento\Framework\Exception\LocalizedException(__("The item '%1' is related to the quote #%2. It's not allowed to change the qty.",$item->getName(), $proposal->getQuote()->getIncrementId()));
                }
            }
        }
        return $this;
    }
}
