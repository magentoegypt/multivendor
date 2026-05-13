<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */
namespace Vnecoms\Quotation\Cron;

use Vnecoms\Quotation\Model\ResourceModel\Quote\CollectionFactory;
use Vnecoms\Quotation\Model\Email as Mailer;
use Vnecoms\Quotation\Model\Quote;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;

class ProcessExpireQuotes
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;
    
    /**
     * @var Mailer
     */
    protected $mailer;
    
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;
    
    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    protected $logger;
    
    /**
     * @param CollectionFactory $collectionFactory
     * @param Mailer $mailer
     * @param DateTime $date
     * @param LoggerInterface $logger
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        Mailer $mailer,
        DateTime $date,
        LoggerInterface $logger
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->mailer = $mailer;
        $this->date = $date;
        $this->logger = $logger;
    }

    /**
     * Set all accepted and approved quotes to expired.
     *
     * @return void
     */
    public function execute()
    {
        $today = $this->date->date('Y-m-d');
        $quoteCollection = $this->collectionFactory->create()
            ->addFieldToFilter('status', ['in' => [Quote::STATUS_ACCEPTED, Quote::STATUS_SENT]])
            ->addFieldToFilter('expired_date', ['lt' => $today]);
        
        foreach($quoteCollection as $quote){
            $quote->setStatus(Quote::STATUS_EXPIRED)->save();
            if($quote->getData('expire_enabled')){
                $this->mailer->sendExpiredQuoteEmailToCustomer($quote);
            }
        }
    }
}
