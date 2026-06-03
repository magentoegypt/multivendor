<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Model\Customer;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;

/**
 * Maps a Magento customer to Odoo res.partner values, and computes the checksum
 * of the written representation for echo-suppression.
 */
class CustomerPushMapper
{
    private CustomerMapper $customerMapper;

    public function __construct(CustomerMapper $customerMapper)
    {
        $this->customerMapper = $customerMapper;
    }

    /**
     * @return array<string, mixed>
     */
    public function toOdooValues(CustomerInterface $customer): array
    {
        $name = $this->fullName($customer);
        $values = [
            'name' => $name !== '' ? $name : (string)$customer->getEmail(),
            'email' => (string)$customer->getEmail(),
            'customer_rank' => 1,
        ];

        // Tax/VAT number (B2B).
        $vat = trim((string)$customer->getTaxvat());
        if ($vat !== '') {
            $values['vat'] = $vat;
        }

        $billing = $this->billingAddress($customer);
        if ($billing !== null) {
            $values += $this->addressFields($billing);
        }

        return $values;
    }

    /**
     * Full partner name from the Magento name parts (prefix / first / middle / last / suffix).
     */
    public function fullName(CustomerInterface $customer): string
    {
        $parts = [
            (string)$customer->getPrefix(),
            (string)$customer->getFirstname(),
            (string)$customer->getMiddlename(),
            (string)$customer->getLastname(),
            (string)$customer->getSuffix(),
        ];
        $clean = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '') {
                $clean[] = $part;
            }
        }

        return implode(' ', $clean);
    }

    /**
     * res.partner address-shaped fields from a Magento address. country_id here is
     * the ISO code; the pusher resolves it to the Odoo res.country id.
     *
     * @return array<string, mixed>
     */
    public function addressFields(AddressInterface $address): array
    {
        $out = [];
        $phone = trim((string)$address->getTelephone());
        if ($phone !== '') {
            $out['phone'] = $phone;
        }
        $street = $address->getStreet();
        if (is_array($street)) {
            if (isset($street[0]) && $street[0] !== '') {
                $out['street'] = (string)$street[0];
            }
            if (isset($street[1]) && $street[1] !== '') {
                $out['street2'] = (string)$street[1];
            }
        }
        if ($address->getCity()) {
            $out['city'] = (string)$address->getCity();
        }
        if ($address->getPostcode()) {
            $out['zip'] = (string)$address->getPostcode();
        }

        return $out;
    }

    public function billingAddress(CustomerInterface $customer): ?AddressInterface
    {
        $byId = $this->addressById($customer, (string)$customer->getDefaultBilling());
        if ($byId !== null) {
            return $byId;
        }
        $addresses = (array)$customer->getAddresses();

        return $addresses[0] ?? null;
    }

    public function shippingAddress(CustomerInterface $customer): ?AddressInterface
    {
        return $this->addressById($customer, (string)$customer->getDefaultShipping());
    }

    public function billingCountryCode(CustomerInterface $customer): ?string
    {
        $billing = $this->billingAddress($customer);
        $code = $billing !== null ? trim((string)$billing->getCountryId()) : '';

        return $code !== '' ? $code : null;
    }

    public function billingRegionCode(CustomerInterface $customer): ?string
    {
        $billing = $this->billingAddress($customer);

        return $billing !== null ? $this->regionCode($billing) : null;
    }

    /**
     * Region/state code from a Magento customer address (e.g. CA); the pusher
     * resolves it to the Odoo res.country.state id within the address country.
     */
    public function regionCode(AddressInterface $address): ?string
    {
        $region = $address->getRegion();
        if ($region === null) {
            return null;
        }
        $code = trim((string)$region->getRegionCode());

        return $code !== '' ? $code : null;
    }

    private function addressById(CustomerInterface $customer, string $addressId): ?AddressInterface
    {
        if ($addressId === '') {
            return null;
        }
        foreach ((array)$customer->getAddresses() as $address) {
            if ((string)$address->getId() === $addressId) {
                return $address;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $odooValues
     */
    public function expectedOdooChecksum(array $odooValues): string
    {
        return $this->customerMapper->checksum([
            'email' => $odooValues['email'] ?? false,
            'name' => $odooValues['name'] ?? false,
            'phone' => $odooValues['phone'] ?? false,
        ]);
    }
}
