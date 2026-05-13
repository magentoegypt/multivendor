<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Google Address Autocomplete for Magento 2 (System)
 */

namespace Amasty\GoogleAddressAutocomplete\ViewModel;

use Amasty\GoogleAddressAutocomplete\Model\ConfigProvider;
use Amasty\GoogleAddressAutocomplete\Model\GetRegionsList;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class Init implements ArgumentInterface
{
    /**
     * @var GetRegionsList
     */
    private $getRegionsList;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(
        GetRegionsList $getRegionsList,
        Json $jsonSerializer,
        ConfigProvider $configProvider
    ) {
        $this->getRegionsList = $getRegionsList;
        $this->jsonSerializer = $jsonSerializer;
        $this->configProvider = $configProvider;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->configProvider->isAddressSuggestionEnabled();
    }

    /**
     * @return string|null
     */
    public function getApiKey(): ?string
    {
        return $this->configProvider->getGoogleMapsKey();
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return [
            'regions' => $this->getRegionsList->execute()
        ];
    }

    /**
     * @return string
     */
    public function getOptionsJson(): string
    {
        return $this->jsonSerializer->serialize($this->getOptions());
    }

    public function getRestrictedCountryList(): string
    {
        $countriesArray = [];
        if (!empty($countriesString = $this->configProvider->getRestrictedCountryList())) {
            $countriesArray = explode(',', $countriesString);
        }

        return $this->jsonSerializer->serialize($countriesArray);
    }
}
