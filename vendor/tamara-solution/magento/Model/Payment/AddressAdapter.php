<?php
namespace Tamara\Checkout\Model\Payment;

class AddressAdapter
{
    private $address;

    public function __construct($address)
    {
        $this->address = $address;
    }

    public function getFirstname()
    {
        return $this->address->getFirstname();
    }

    public function getLastname()
    {
        return $this->address->getLastname();
    }

    public function getMiddlename()
    {
        return $this->address->getMiddlename();
    }

    public function getCustomerId()
    {
        return $this->address->getCustomerId();
    }

    public function getCustomerAddressId()
    {
        return $this->address->getCustomerAddressId();
    }

    public function getEmail()
    {
        return $this->address->getEmail();
    }

    public function getPrefix()
    {
        return $this->address->getPrefix();
    }

    public function getSuffix()
    {
        return $this->address->getSuffix();
    }

    public function getCompany()
    {
        return $this->address->getCompany();
    }

    public function getStreet()
    {
        return $this->address->getStreet();
    }

    public function getCity()
    {
        return $this->address->getCity();
    }

    public function getRegionCode()
    {
        return $this->address->getRegionCode();
    }

    public function getRegion()
    {
        return $this->address->getRegion();
    }

    public function getPostcode()
    {
        return $this->address->getPostcode();
    }

    public function getCountryId()
    {
        return $this->address->getCountryId();
    }

    public function getTelephone()
    {
        return $this->address->getTelephone();
    }

    public function getFax()
    {
        return $this->address->getFax();
    }

    public function getStreetLine1()
    {
        $street = $this->address->getStreet();
        return is_array($street) && isset($street[0]) ? $street[0] : '';
    }

    public function getStreetLine2()
    {
        $street = $this->address->getStreet();
        return is_array($street) && isset($street[1]) ? $street[1] : '';
    }
}
