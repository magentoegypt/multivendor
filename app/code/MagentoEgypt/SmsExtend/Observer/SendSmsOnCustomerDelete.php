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
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Constructor method for SendSmsOnCustomerDelete observer.
     *
     * $logger is optional ON PURPOSE. This class is already in the compiled DI
     * (generated/metadata) on a production install, and compiled factories call
     * the constructor with the argument list they were compiled against — a new
     * REQUIRED parameter would be an ArgumentCountError on the first delete,
     * before anyone ran di:compile. Optional + an ObjectManager fallback is the
     * safe way to take a new dependency here.
     *
     * @param \Vnecoms\Sms\Helper\Data $helper Helper class for SMS functionality.
     * @param \MagentoEgypt\SmsExtend\Helper\Data $configHelper Helper class for SMS configuration.
     * @param \Magento\Email\Model\Template\Filter $filter Email template filter model.
     * @param EmailNotification $emailNotification Email notification model.
     * @param \Psr\Log\LoggerInterface|null $logger
     */
    public function __construct(
        \Vnecoms\Sms\Helper\Data $helper,
        \MagentoEgypt\SmsExtend\Helper\Data $configHelper,
        \Magento\Email\Model\Template\Filter $filter,
        EmailNotification $emailNotification,
        \Psr\Log\LoggerInterface $logger = null
    ) {
        $this->helper = $helper;
        $this->configHelper = $configHelper;
        $this->filter = $filter;
        $this->emailNotification = $emailNotification;
        $this->logger = $logger ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Psr\Log\LoggerInterface::class);
    }

    /**
     * Notify a customer that their account was removed.
     *
     * BOTH NOTIFICATIONS ARE GUARDED, AND THAT IS THE POINT OF THIS OBSERVER.
     * It runs on `customer_delete_after`, which is inside the delete
     * transaction, so anything that escapes here does not merely lose the
     * notification — it ROLLS THE DELETION BACK. Until 2026-09-22 the e-mail
     * was unguarded, and because mail transport on this host was failing
     * (`MailException: Transport error: Unable to send mail at this time`),
     * every customer deletion failed: the admin showed a mail error and the
     * account was still there afterwards. It was not specific to one address —
     * the failure is at transport level, so it applied to every customer.
     *
     * A courtesy message must never be able to veto an operator's deletion.
     * Both sends are now best-effort and logged.
     *
     * `\Throwable`, not `\Exception`: the sibling Vnecoms vendor-mail path has
     * already been seen throwing a TypeError rather than an exception, and an
     * Error escaping here would roll the delete back exactly the same way.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->helper->getCurrentGateway()) {
            return $this;
        }

        $customer = $observer->getCustomer();
        $customerId = $customer ? $customer->getId() : null;

        try {
            $this->emailNotification->removeAccount($customer);
        } catch (\Throwable $e) {
            $this->logger->error(
                'Account-removed e-mail failed; the deletion itself was not affected.',
                ['customer_id' => $customerId, 'exception' => $e]
            );
        }

        /* Send vendor account approved sms message*/
        if ($this->configHelper->canSendCustomerDeleteMessage()) {
            try {
                $message = $this->configHelper->getCustomerDeleteMessage();
                $this->filter->setVariables(['customer' => $customer]);
                $message = $this->filter->filter($message);
                $this->helper->sendCustomerSms($customer, $message);
            } catch (\Throwable $e) {
                $this->logger->error(
                    'Account-removed SMS failed; the deletion itself was not affected.',
                    ['customer_id' => $customerId, 'exception' => $e]
                );
            }
        }

        return $this;
    }
}
