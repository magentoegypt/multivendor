<?php
namespace MagentoEgypt\SmsExtend\Observer;

use Magento\Framework\Event\ObserverInterface;
use MagentoEgypt\SmsExtend\Model\EmailNotification;

class SendSmsOnCustomerDelete implements ObserverInterface
{
    /**
     * @var \Vnecoms\Sms\Helper\Data
     */
    protected $helper;

    /**
     * @var \MagentoEgypt\SmsExtend\Helper\Data
     */
    protected $configHelper;

    /**
     * @var \Magento\Email\Model\Template\Filter
     */
    protected $filter;

    /**
     * @var EmailNotification
     */
    protected $emailNotification;

    /**
     * Constructor method for SendSmsOnCustomerDelete observer.
     *
     * @param \Vnecoms\Sms\Helper\Data $helper Helper class for SMS functionality.
     * @param \MagentoEgypt\SmsExtend\Helper\Data $configHelper Helper class for SMS configuration.
     * @param \Magento\Email\Model\Template\Filter $filter Email template filter model.
     * @param EmailNotification $emailNotification Email notification model.
     */
    public function __construct(
        \Vnecoms\Sms\Helper\Data $helper,
        \MagentoEgypt\SmsExtend\Helper\Data $configHelper,
        \Magento\Email\Model\Template\Filter $filter,
        EmailNotification $emailNotification
    ) {
        $this->helper = $helper;
        $this->configHelper = $configHelper;
        $this->filter = $filter;
        $this->emailNotification = $emailNotification;
    }

    /**
     * Vendor delete After
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if(!$this->helper->getCurrentGateway()) return;

        $customer = $observer->getCustomer();

        $this->emailNotification->removeAccount($customer);

        /* Send vendor account approved sms message*/
        if($this->configHelper->canSendCustomerDeleteMessage()){
            $message = $this->configHelper->getCustomerDeleteMessage();
            $this->filter->setVariables(['customer' => $customer]);
            $message = $this->filter->filter($message);
            $this->helper->sendCustomerSms($customer, $message);
        }
        return $this;
    }
}