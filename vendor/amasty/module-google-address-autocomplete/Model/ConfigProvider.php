<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Google Address Autocomplete for Magento 2 (System)
 */

namespace Amasty\GoogleAddressAutocomplete\Model;

use Amasty\Base\Model\ConfigProviderAbstract;

class ConfigProvider extends ConfigProviderAbstract
{
    public const PATH_PREFIX = 'amasty_address_autocomplete/';

    public const IS_ENABLED = 'general/google_address_suggestion';
    public const API_KEY = 'general/google_api_key';
    public const AUTOCOMPLETE_COUNTRY_RESTRICTIONS = 'general/autocomplete_country_restrictions';

    /**
     * @var string
     */
    protected $pathPrefix = self::PATH_PREFIX;

    /**
     * @return bool
     */
    public function isAddressSuggestionEnabled(): bool
    {
        return $this->isSetFlag(self::IS_ENABLED);
    }

    /**
     * @return string|null
     */
    public function getGoogleMapsKey(): ?string
    {
        return $this->getValue(self::API_KEY);
    }

    /**
     * @return string|null
     */
    public function getRestrictedCountryList(): ?string
    {
        return $this->getValue(self::AUTOCOMPLETE_COUNTRY_RESTRICTIONS);
    }
}
