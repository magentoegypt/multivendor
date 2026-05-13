<?php

namespace Vnecoms\Quotation\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\ObjectManager;
use Vnecoms\Quotation\Model\Quote;

class CheckoutSubmitAll implements ObserverInterface
{
    /**
     * @var \Vnecoms\Quotation\Model\Email
     */
    protected $mailer;
    
    /**
     * Application Event Dispatcher
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;
    
    /**
     * @param \Vnecoms\Quotation\Model\Email $mailer
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     */
    public function __construct(
        \Vnecoms\Quotation\Model\Email $mailer,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ){
        $this->mailer = $mailer;
        $this->eventManager = $eventManager;
    }
    
    /**
     * Update free gift quanty
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote */
        $quote = $observer->getQuote();
        
        foreach($quote->getAllItems() as $item){
            if($proposalId = $item->getOptionByCode('quotation_proposal_id')){
                $proposalId = $proposalId->getValue();
                $proposal = ObjectManager::getInstance()->create('Vnecoms\Quotation\Model\Proposal')->load($proposalId);
                $quotation = $proposal->getQuote();
                if(!$quotation->getId()) continue;
                if($quotation->getStatus() != Quote::STATUS_ORDERED){
                    $quotation->setStatus(Quote::STATUS_ORDERED)->save();
                    $this->mailer->sendOrderedQuoteEmailToAdmin($quotation);
                    $this->eventManager->dispatch('ves_quotation_quote_ordered', ['quote' => $quotation]);
                }
            }
        }
        return $this;
    }
}
