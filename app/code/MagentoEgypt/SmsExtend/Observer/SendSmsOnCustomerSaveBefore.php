<?php
namespace MagentoEgypt\SmsExtend\Observer;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use MagentoEgypt\SmsExtend\Helper\Otp;

/**
 * A customer's mobile number must not belong to another account.
 *
 * Uses the shared Otp::isMobileUsedByAnotherAccount() (TC73 14zb93nv6vw), so this save check, the WhatsApp OTP
 * checks and vendor registration agree on what "already exists" means: every stored spelling of a number
 * (+20…, 20…, 0…, bare) counts, and the account being saved never counts against itself. This check used to match
 * the exact string only, so "+201…" and "01…" could end up on two different accounts.
 */
class SendSmsOnCustomerSaveBefore implements ObserverInterface
{
    private $otp;

    public function __construct(?Otp $otp = null)
    {
        /* Optional with a fallback so the compiled DI config keeps working until the next di:compile. */
        $this->otp = $otp ?: ObjectManager::getInstance()->get(Otp::class);
    }

    public function execute(Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        $mobile = trim((string)$customer->getData('mobilenumber'));
        if ($mobile === '') {
            return $this;
        }

        $websiteId = $customer->getSharingConfig()->isWebsiteScope() ? (int)$customer->getWebsiteId() : null;
        $customerId = $customer->getId() ? (int)$customer->getId() : null;
        if ($this->otp->isMobileUsedByAnotherAccount($mobile, $customerId, $websiteId)) {
            throw new LocalizedException(
                __('Mobile number already exists.')
            );
        }

        return $this;
    }
}
