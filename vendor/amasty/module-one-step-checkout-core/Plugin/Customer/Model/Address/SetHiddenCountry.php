<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Plugin\Customer\Model\Address;

use Amasty\CheckoutCore\Model\Field;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Model\Address;
use Magento\Framework\App\RequestInterface;

/**
 * Set Hidden CountryId Value To DB Entity For Load Address In Future
 */
class SetHiddenCountry
{
    /**
     * @var Field
     */
    private $fieldSingleton;

    /**
     * @var RequestInterface
     */
    private $request;

    public function __construct(Field $fieldSingleton, RequestInterface $request)
    {
        $this->fieldSingleton = $fieldSingleton;
        $this->request = $request;
    }

    /**
     * @param Address $subject
     * @param AddressInterface $address
     * @return array
     */
    public function beforeUpdateData(Address $subject, AddressInterface $address): array
    {
        $countryId = $this->request->getParam('country_id', false);
        if (!$countryId || $address->getCountryId()) {
            return [$address];
        }

        $fieldConfig = $this->fieldSingleton->getConfig((int) $subject->getStoreId());

        $isCountryEnabled = isset($fieldConfig[AddressInterface::COUNTRY_ID])
            && $fieldConfig[AddressInterface::COUNTRY_ID]->isEnabled();
        $isRegionRequired = isset($fieldConfig[AddressInterface::REGION])
            && $fieldConfig[AddressInterface::REGION]->getIsRequired();

        if (!$isCountryEnabled || !$isRegionRequired) {
            $address->setCountryId($countryId);
        }

        return [$address];
    }
}
