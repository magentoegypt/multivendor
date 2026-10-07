<?php
/**
 * A guest order cannot be placed with an email whose domain cannot receive mail (CL036-TC97).
 *
 * A guest's only copy of the order confirmation, the shipping updates and the
 * invoice go to that address; on a dead domain the customer is left with
 * nothing. Checked where the storefront checkout places a guest order (the
 * Payment step posts to /V1/guest-carts/:cartId/payment-information).
 * Fails open on a DNS error, like the account guard.
 */
declare(strict_types=1);

namespace MagentoEgypt\AccountExtend\Plugin\Checkout;

use Magento\Checkout\Api\GuestPaymentInformationManagementInterface;
use Magento\Framework\Exception\InputException;
use MagentoEgypt\AccountExtend\Model\EmailDeliverability;
use MagentoEgypt\AccountExtend\Model\UndeliverableEmailMessage;

class RejectUndeliverableGuestEmail
{
    public function __construct(
        private readonly EmailDeliverability $deliverability
    ) {
    }

    /**
     * @param GuestPaymentInformationManagementInterface $subject
     * @param string $cartId
     * @param string $email
     * @param mixed ...$rest
     * @return null
     * @throws InputException
     */
    public function beforeSavePaymentInformationAndPlaceOrder(
        GuestPaymentInformationManagementInterface $subject,
        $cartId,
        $email,
        ...$rest
    ) {
        $check = $this->deliverability->check((string) $email);
        if ($check['deliverable'] === false) {
            throw new InputException(UndeliverableEmailMessage::from($check));
        }

        return null;
    }
}
