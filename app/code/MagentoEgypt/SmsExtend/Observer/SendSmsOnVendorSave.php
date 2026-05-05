<?php
namespace MagentoEgypt\SmsExtend\Observer;

use Vnecoms\VendorsSms\Observer\VendorSaveBefore as BaseVendorSaveBefore;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MagentoEgypt\SmsExtend\Helper\Data as ConfigHelper;
use Vnecoms\Vendors\Model\Vendor;

class SendSmsOnVendorSave extends BaseVendorSaveBefore implements ObserverInterface
{
    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * Constructor
     *
     * @param ConfigHelper $configHelper
     */
    public function __construct(
        ConfigHelper $configHelper,
        \Vnecoms\VendorsSms\Helper\Data $helper,
        \Magento\Email\Model\Template\Filter $filter
    ) {
        $this->configHelper = $configHelper;
        parent::__construct($helper, $filter);
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
        $status = $vendor->getData('status');
        if($vendor->isObjectNew()){
            /* Send notification message to admin if the vendor account need to be approved*/
            if($status == Vendor::STATUS_PENDING){
                if($this->configHelper->canSendVendorRegisterMessage()){
                    $message = $this->configHelper->getVendorRegisterMessage();
                    $this->filter->setVariables(['vendor' => $vendor]);
                    $message = $this->filter->filter($message);
                    $this->helper->sendSms($vendor, $message);
                }
            }
        }

        // Call parent execute method
        parent::execute($observer);
    }
}