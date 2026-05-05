<?php
namespace MagentoEgypt\SmsExtend\Observer;

use Magento\Framework\Event\ObserverInterface;

class SendSmsOnCustomerEdit implements ObserverInterface
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
     * @var \Magento\Customer\Model\CustomerRegistry
     */
    protected $customerRegistry;

    /**
     * Constructor method for SendSmsOnCustomerEdit observer.
     *
     * @param \Vnecoms\Sms\Helper\Data $helper Helper class for SMS functionality.
     * @param \MagentoEgypt\SmsExtend\Helper\Data $configHelper Helper class for SMS configuration.
     * @param \Magento\Email\Model\Template\Filter $filter Email template filter model.
     * @param \Magento\Customer\Model\CustomerRegistry $customerRegistry Customer registry model.
     */
    public function __construct(
        \Vnecoms\Sms\Helper\Data $helper,
        \MagentoEgypt\SmsExtend\Helper\Data $configHelper,
        \Magento\Email\Model\Template\Filter $filter,
        \Magento\Customer\Model\CustomerRegistry $customerRegistry
    ) {
        $this->helper = $helper;
        $this->configHelper = $configHelper;
        $this->filter = $filter;
        $this->customerRegistry = $customerRegistry;
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

        $customerEmail = $observer->getEmail();
        $customer = $this->customerRegistry->retrieveByEmail($customerEmail);

        /* Send vendor account approved sms message*/
        if($this->configHelper->canSendCustomerEditMessage()){
            $message = $this->configHelper->getCustomerEditMessage();
            $this->filter->setVariables(['customer' => $customer]);
            $message = $this->filter->filter($message);
            $this->helper->sendCustomerSms($customer, $message);
        }
        return $this;
    }
}