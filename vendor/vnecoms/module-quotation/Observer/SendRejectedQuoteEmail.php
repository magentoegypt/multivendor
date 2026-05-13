<?php

namespace Vnecoms\Quotation\Observer;

use Magento\Framework\Event\ObserverInterface;

class SendRejectedQuoteEmail implements ObserverInterface
{
    /**
     * @var \Vnecoms\Quotation\Model\Email
     */
    protected $mailer;
    
    /**
     * @param unknown $mailer
     */
    public function __construct(
        \Vnecoms\Quotation\Model\Email $mailer
    ) {
        $this->mailer = $mailer;
    }
    
    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @codeCoverageIgnore
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->mailer->sendRejectedQuoteEmailToCustomer($observer->getQuote());
        $this->mailer->sendRejectedQuoteEmailToAdmin($observer->getQuote());
    }
}
