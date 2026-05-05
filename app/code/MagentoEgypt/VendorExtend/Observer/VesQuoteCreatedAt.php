<?php
namespace MagentoEgypt\VendorExtend\Observer;

use Magento\Framework\Event\ObserverInterface;

class VesQuoteCreatedAt implements ObserverInterface
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * VesQuoteCreatedAt constructor.
     *
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ) {
        $this->timezone = $timezone;
    }

    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getQuote();
        if ($quote && $quote->getId()) {
            $quote->setCreatedAt(date('Y-m-d H:i:s', $this->timezone->scopeTimeStamp()));
        }
    }
}
