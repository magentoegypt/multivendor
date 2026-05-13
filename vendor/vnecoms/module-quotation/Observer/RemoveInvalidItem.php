<?php

namespace Vnecoms\Quotation\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\ObjectManager;
use Vnecoms\Quotation\Model\Quote;

class RemoveInvalidItem implements ObserverInterface
{    
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    
    /**
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->messageManager = $messageManager;
    }
    
    /**
     * Add free gift to shopping cart.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var $quote \Magento\Quote\Model\Quote */
        $quote = $observer->getQuote();
        
        foreach($quote->getAllItems() as $item){
            if($proposalId = $item->getOptionByCode('quotation_proposal_id')){
                $proposalId = $proposalId->getValue();
                $proposal = ObjectManager::getInstance()->create('Vnecoms\Quotation\Model\Proposal')->load($proposalId);
                $quotation = $proposal->getQuote();
                if($quotation->getStatus() != Quote::STATUS_ACCEPTED) {
                    /* $quote->removeItem($item->getId());
                    $message = $quotation->getStatus() == Quote::STATUS_EXPIRED?
                        __('The quote #%1 is expired. The item "%2" will be removed automatically.',$quotation->getIncrementId(), $item->getName()):
                        __('The quote #%1 is not valid. The item "%2" will be removed automatically.',$quotation->getIncrementId(), $item->getName());
                    
                    $this->messageManager->addSuccess($message); */
                }
            }
        }
    }
}
