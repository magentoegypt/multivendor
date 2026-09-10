<?php
/**
 * One address entered once must not become two rows in the address book.
 *
 * WHAT THE CLIENT SAW
 * -------------------
 * After placing an order, the checkout's address list showed the same address
 * twice — same name, street, city, postcode and phone. In the database they are
 * two rows created a second apart, one set as the customer's default SHIPPING
 * and the other as their default BILLING:
 *
 *     #23  2026-09-10 13:33:28  test dev5 / New York / 10001 / +201002004847
 *     #24  2026-09-10 13:33:29  test dev5 / New York / 10001 / +201002004847
 *
 * WHERE THEY COME FROM
 * --------------------
 * Magento\Quote\Model\QuoteManagement::_prepareCustomerQuote() copies the
 * quote's addresses into the address book at placement, in two independent
 * branches — one for shipping, one for billing. It only reuses the billing row
 * for shipping in the narrow case where the shipping address has no
 * customer_address_id AND is flagged same_as_billing; when the checkout has
 * already assigned an id to the shipping address, both branches save, and a
 * customer who typed one address ends up with two identical entries. The method
 * is protected, so it cannot be intercepted directly.
 *
 * WHAT THIS DOES
 * --------------
 * Both branches save through AddressRepositoryInterface::save(), so that is the
 * chokepoint. A NEW address (no id) that is field-for-field identical to one the
 * customer already has is not created a second time: the existing row is used
 * instead, carrying over whichever default flags the caller was about to set.
 * The caller gets back an address with an id, as it expects, and the quote
 * points both of its addresses at the one row.
 *
 * Deliberately NOT limited to checkout. An identical second address is noise
 * wherever it is created — the account's address book, the admin, the REST API
 * — and every one of them saves through here.
 *
 * An UPDATE is never touched: editing an address into the shape of another one
 * is a deliberate act, and refusing it would lose the edit.
 */
declare(strict_types=1);

namespace MagentoEgypt\CheckoutExtend\Plugin\Customer;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Model\ResourceModel\Address\CollectionFactory;
use Psr\Log\LoggerInterface;

class DeduplicateAddress
{
    public function __construct(
        private readonly CollectionFactory $addressCollectionFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param AddressRepositoryInterface $subject
     * @param callable $proceed
     * @param AddressInterface $address
     * @return AddressInterface
     */
    public function aroundSave(
        AddressRepositoryInterface $subject,
        callable $proceed,
        AddressInterface $address
    ): AddressInterface {
        if ($address->getId() || !$address->getCustomerId()) {
            return $proceed($address);
        }

        try {
            $twinId = $this->findTwinId($address);
        } catch (\Throwable $e) {
            /* A duplicate is a blemish; a failed order is not. Let it through. */
            $this->logger->warning('Address de-duplication skipped: ' . $e->getMessage());

            return $proceed($address);
        }

        if ($twinId === null) {
            return $proceed($address);
        }

        /*
         * The ID GOES ON THE CALLER'S OBJECT, and that object is what saves.
         *
         * Saving the existing address instead and returning it looks tidier and
         * is wrong: _prepareCustomerQuote() throws the return value away and
         * reads the id back off the object it passed in —
         *
         *     $this->addressRepository->save($shippingAddress);
         *     $shipping->setCustomerAddressId($shippingAddress->getId());
         *
         * — so the quote, and then the order, would have come out with a null
         * customer_address_id. Measured: that is exactly what happened on the
         * first cut of this plugin.
         *
         * With the id set, the save is an UPDATE of the row that already exists.
         * Nothing is lost by it: the data matched field for field, that is what
         * made it a twin, and Magento's updateData() only writes the fields the
         * incoming object actually carries — so the default-billing and
         * default-shipping flags the caller set are applied, and any it did not
         * set stay as they were.
         */
        $address->setId($twinId);

        return $proceed($address);
    }

    /**
     * The id of an existing address of this customer that is the same address.
     */
    private function findTwinId(AddressInterface $address): ?int
    {
        $wanted = $this->signature(
            (string) $address->getFirstname(),
            (string) $address->getLastname(),
            (string) $address->getCompany(),
            implode(' ', (array) $address->getStreet()),
            (string) $address->getCity(),
            (string) ($address->getRegion() ? $address->getRegion()->getRegion() : ''),
            (string) ($address->getRegionId() ?: ''),
            (string) $address->getPostcode(),
            (string) $address->getCountryId(),
            (string) $address->getTelephone()
        );

        $collection = $this->addressCollectionFactory->create();
        $collection->addFieldToFilter('parent_id', (int) $address->getCustomerId());

        foreach ($collection as $existing) {
            $mine = $this->signature(
                (string) $existing->getFirstname(),
                (string) $existing->getLastname(),
                (string) $existing->getCompany(),
                str_replace("\n", ' ', (string) $existing->getStreetFull()),
                (string) $existing->getCity(),
                (string) $existing->getRegion(),
                (string) ($existing->getRegionId() ?: ''),
                (string) $existing->getPostcode(),
                (string) $existing->getCountryId(),
                (string) $existing->getTelephone()
            );

            if ($mine === $wanted) {
                return (int) $existing->getId();
            }
        }

        return null;
    }

    /**
     * Case- and whitespace-insensitive: "New York" and "new  york" are one city,
     * and a trailing space is not a different address.
     */
    private function signature(string ...$parts): string
    {
        $parts = array_map(
            static fn (string $p): string => mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $p))),
            $parts
        );

        return implode('|', $parts);
    }
}
