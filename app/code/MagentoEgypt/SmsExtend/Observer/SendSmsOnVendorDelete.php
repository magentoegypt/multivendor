<?php
namespace MagentoEgypt\SmsExtend\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MagentoEgypt\SmsExtend\Helper\Data as ConfigHelper;

class SendSmsOnVendorDelete implements ObserverInterface
{
    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * @var \Vnecoms\VendorsSms\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Email\Model\Template\Filter
     */
    protected $filter;

    /**
     * Constructor
     *
     * @param ConfigHelper $configHelper
     * @param \Vnecoms\VendorsSms\Helper\Data $helper
     * @param \Magento\Email\Model\Template\Filter $filter
     */
    public function __construct(
        ConfigHelper $configHelper,
        \Vnecoms\VendorsSms\Helper\Data $helper,
        \Magento\Email\Model\Template\Filter $filter
    ) {
        $this->helper = $helper;
        $this->filter = $filter;
        $this->configHelper = $configHelper;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $vendor = $observer->getVendor();
        if($vendor->getId())
        {
            if($this->configHelper->canSendVendorEditMessage()){
                $message = $this->configHelper->getVendorEditMessage();
                $this->filter->setVariables(['vendor' => $vendor]);
                $message = $this->filter->filter($message);
                $this->helper->sendSms($vendor, $message);
            }
        }
    }
}