<?php
/**
 * A vendor's notification mail must never be what fails the shopper's order.
 *
 * WHAT QA SAW ([CL036-TC09] "Place Order – Login")
 * ------------------------------------------------
 * "When we try to place an order while logged into an account, an error
 * happens." The order HAD been placed: 3000000010 exists, and the customer's
 * own confirmation went out (email_sent = 1). What failed was what came after.
 *
 * WHY
 * ---
 * Vnecoms\VendorsSales\Observer\ProcessOrder is dispatched from inside
 * placeOrder(), after the order is saved. For each vendor in the order it saves
 * the vendor sub-order and then mails the vendor through OrderSender, addressed
 * to $vendor->getEmail() — the e-mail of the customer account linked to the
 * vendor in ves_vendor_user. A vendor with no linked account resolves to null
 * (6 of the 29 vendors on 2026-09-11; two of them, 2 and 12, have products on
 * sale), and TransportBuilder::addTo(null) throws a TypeError.
 *
 * Vnecoms' Sender::checkAndSend() wraps the send in `catch (\Exception)`, and a
 * TypeError is an \Error, not an \Exception. So it escaped the observer, escaped
 * placeOrder(), and checkout reported a failure for an order that had
 * succeeded. It happened twice, both times for vendor 2's products: 2026-09-08
 * 17:53 UTC (3000000010) and 2026-09-10 17:49 UTC (000000086). Nothing about it
 * was specific to logged-in shoppers — a guest buying the same products took the
 * same path. In a cart with several vendors it was worse: the loop stopped at the
 * first vendor with no address, and every vendor after it got no sub-order.
 *
 * THE GUARD
 * ---------
 * checkAndSend() is protected, so this sits on each sender's public send():
 * order, invoice, shipment and credit memo all mail the vendor the same way. Any
 * \Throwable from a vendor mail is logged and the mail reported as not sent —
 * what Vnecoms already does for an \Exception. Those vendors still get no mail;
 * giving them accounts is a data job. This only makes sure the missing address
 * can no longer take the shopper's checkout down with it.
 */
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Plugin\VendorsSales;

use Magento\Framework\DataObject;
use Psr\Log\LoggerInterface;

class VendorEmailCannotFailTheOrder
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param object $subject a Vnecoms\VendorsSales\Model\Order\Email\Sender\*Sender
     * @param callable $proceed
     * @param object $entity the vendor order, invoice, shipment or credit memo being announced
     * @param bool $forceSyncMode
     * @return bool
     */
    public function aroundSend($subject, callable $proceed, $entity, $forceSyncMode = false)
    {
        try {
            return $proceed($entity, $forceSyncMode);
        } catch (\Throwable $e) {
            $this->logger->error(sprintf(
                'Vendor mail not sent (%s, entity %s, vendor %s): %s: %s',
                $this->senderName($subject),
                $entity instanceof DataObject ? (string) $entity->getId() : '?',
                $entity instanceof DataObject ? (string) $entity->getData('vendor_id') : '?',
                get_class($e),
                $e->getMessage()
            ));

            return false;
        }
    }

    private function senderName(object $subject): string
    {
        $class = preg_replace('/\\\\Interceptor$/', '', get_class($subject)) ?? get_class($subject);
        $slash = strrpos($class, '\\');

        return $slash === false ? $class : substr($class, $slash + 1);
    }
}
