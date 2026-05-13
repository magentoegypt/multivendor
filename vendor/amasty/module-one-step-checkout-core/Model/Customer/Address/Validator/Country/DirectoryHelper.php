<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Customer\Address\Validator\Country;

use Amasty\CheckoutCore\Model\Field;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Directory\Model\ResourceModel\Country\Collection;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory;
use Magento\Framework\App\Cache\Type\Config;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Json\Helper\Data as JsonData;
use Magento\Store\Model\StoreManagerInterface;

class DirectoryHelper extends \Magento\Directory\Helper\Data
{
    /**
     * @var Field
     */
    private $fieldSingleton;

    public function __construct(
        Context $context,
        Config $configCacheType,
        Collection $countryCollection,
        CollectionFactory $regCollectionFactory,
        JsonData $jsonHelper,
        StoreManagerInterface $storeManager,
        CurrencyFactory $currencyFactory,
        Field $fieldSingleton
    ) {
        $this->fieldSingleton = $fieldSingleton;

        parent::__construct(
            $context,
            $configCacheType,
            $countryCollection,
            $regCollectionFactory,
            $jsonHelper,
            $storeManager,
            $currencyFactory
        );
    }

    /**
     * @param string $countryId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isRegionRequired($countryId): bool
    {
        $fieldConfig = $this->fieldSingleton->getConfig(
            (int) $this->_storeManager->getStore()->getId()
        );

        $isCountryEnabled = isset($fieldConfig[AddressInterface::COUNTRY_ID])
            && $fieldConfig[AddressInterface::COUNTRY_ID]->isEnabled();
        $isRegionRequired = isset($fieldConfig[AddressInterface::REGION])
            && $fieldConfig[AddressInterface::REGION]->getIsRequired();

        if (!$isCountryEnabled || !$isRegionRequired) {
            return false;
        }

        return parent::isRegionRequired($countryId);
    }
}
