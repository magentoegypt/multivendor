<?php

namespace Vnecoms\VendorsCms\Observer\Vendors;

use Magento\Framework\Event\ObserverInterface;

class CheckEnableModule implements ObserverInterface
{
    protected $helperData;

    public function __construct(
        \Vnecoms\VendorsCms\Helper\Data $helperData
    ) {
        $this->helperData = $helperData;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        // TODO: Implement execute() method.
        if (!$this->helperData->isEnabled()) {
        }
    }
}
