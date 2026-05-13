<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model;

use Amasty\Base\Model\ConfigProviderAbstract;
use Magento\CheckoutAgreements\Model\AgreementsProvider;
use Magento\Customer\Model\AccountManagement as MagentoAccountManagement;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Newsletter\Model\Subscriber;
use Magento\Quote\Model\Quote\Address;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Config for manage global settings
 */
class Config extends ConfigProviderAbstract
{
    /**
     * xpath prefix of module (section)
     *
     * @var string
     */
    protected $pathPrefix = self::PATH_PREFIX;

    /**
     * Path Prefix For Config
     */
    public const PATH_PREFIX = 'amasty_checkout/';

    public const GENERAL_BLOCK = 'general/';
    public const GEOLOCATION_BLOCK = 'geolocation/';
    public const DEFAULT_VALUES = 'default_values/';
    public const DESIGN_BLOCK = 'design/';
    public const ADDITIONAL_OPTIONS = 'additional_options/';
    public const CUSTOM_BLOCK = 'custom_blocks/';

    public const FIELD_ENABLED = 'enabled';
    public const FIELD_EDIT_OPTIONS = 'allow_edit_options';
    public const SHIPPING_ADDRESS_IN = 'display_shipping_address_in';
    public const FIELD_LAYOUT_BUILDER_CONFIG = 'layout_builder_config';
    public const FIELD_CHECKOUT_DESIGN = 'checkout_design';
    public const FIELD_CHECKOUT_LAYOUT = 'layout';
    public const FIELD_SOCIAL_LOGIN = 'social_login';

    public const VALUE_ORDER_TOTALS = 'order_totals';
    public const VALUE_SOCIAL_LOGIN_ENABLED = '1';
    public const VALUE_SOCIAL_LOGIN_DISABLED = '0';

    public const SOCIAL_LOGIN_POSITION_PATH = 'amsociallogin/general/login_position';
    public const SOCIAL_LOGIN_CHECKOUT_PAGE_POSITION = 'checkout';

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var ReinitableConfigInterface
     */
    private $reinitableConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        ReinitableConfigInterface $reinitableConfig
    ) {
        parent::__construct($scopeConfig);
        $this->configWriter = $configWriter;
        $this->reinitableConfig = $reinitableConfig;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->isSetFlag(self::GENERAL_BLOCK . self::FIELD_ENABLED);
    }

    /**
     * @param string $position
     *
     * @return string|int
     */
    public function getCustomBlockIdByPosition($position)
    {
        return $this->getValue(self::CUSTOM_BLOCK . $position . 'block_id');
    }

    /**
     * @return string
     */
    public function isGeolocationEnabled()
    {
        return $this->getValue(self::GEOLOCATION_BLOCK . 'ip_detection');
    }

    /**
     * @return mixed
     */
    public function getPlaceDisplayTermsAndConditions()
    {
        return $this->getAdditionalOptions('display_agreements');
    }

    /**
     * @param string $field
     *
     * @return mixed
     */
    public function getAdditionalOptions($field)
    {
        return $this->getValue(self::ADDITIONAL_OPTIONS . $field);
    }

    /**
     * @return bool
     */
    public function isSetAgreements()
    {
        return $this->scopeConfig->isSetFlag(AgreementsProvider::PATH_ENABLED);
    }

    /**
     * @return bool
     */
    public function isCheckoutItemsEditable()
    {
        return (bool)$this->isSetFlag(self::GENERAL_BLOCK . self::FIELD_EDIT_OPTIONS);
    }

    /**
     * @return array
     */
    public function getDefaultValues()
    {
        return $this->getValue('default_values');
    }

    /**
     * @return string
     */
    public function getDefaultShippingMethod()
    {
        return $this->getValue(self::DEFAULT_VALUES . 'shipping_method');
    }

    /**
     * @return string
     */
    public function getDefaultPaymentMethod()
    {
        return $this->getValue(self::DEFAULT_VALUES . 'payment_method');
    }

    /**
     * @return bool
     */
    public function canShowDob()
    {
        return $this->scopeConfig->getValue(Field::XML_PATH_CONFIG . 'dob_show', ScopeInterface::SCOPE_STORE)
            === Field::MAGENTO_REQUIRE_CONFIG_VALUE;
    }

    /**
     * @return int
     */
    public function getMinimumPasswordLength()
    {
        return $this->scopeConfig->getValue(MagentoAccountManagement::XML_PATH_MINIMUM_PASSWORD_LENGTH);
    }

    /**
     * @return string
     */
    public function getRequiredCharacterClassesNumber()
    {
        return $this->scopeConfig->getValue(MagentoAccountManagement::XML_PATH_REQUIRED_CHARACTER_CLASSES_NUMBER);
    }

    /**
     * @param ?int $storeId
     * @return string
     */
    public function getLayoutTemplate(int $storeId = null): string
    {
        return (string)$this->getValue(self::DESIGN_BLOCK . self::FIELD_CHECKOUT_LAYOUT, $storeId);
    }

    /**
     * @return int
     */
    public function getMultipleShippingAddress()
    {
        return $this->getValue(self::DESIGN_BLOCK . self::SHIPPING_ADDRESS_IN);
    }

    /**
     * @return bool
     */
    public function allowGuestSubscribe()
    {
        return (bool)$this->scopeConfig->getValue(
            Subscriber::XML_PATH_ALLOW_GUEST_SUBSCRIBE_FLAG,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return string
     */
    public function getDefaultCountryId()
    {
        $defaultValue = $this->getValue(self::DEFAULT_VALUES . 'address_country_id');

        if (!$defaultValue) {
            $defaultValue = $this->scopeConfig->getValue('general/country/default', ScopeInterface::SCOPE_STORE);
        }

        return $defaultValue;
    }

    /**
     * @param Address $address
     *
     * @return string
     */
    public function getDefaultRegionId($address)
    {
        $defaultValue = '-';

        $regionCollection = $address->getCountryModel()->getRegionCollection();
        if (!$regionCollection->count() && empty($address->getRegion())) {
            $defaultValue = '-';
            $address->setRegion('-');
        } elseif ($regionCollection->count()
            && !in_array(
                $address->getRegionId(),
                array_column($regionCollection->getData(), 'region_id')
            )
        ) {
            $defaultValue = $this->getValue(self::DEFAULT_VALUES . 'address_region_id');

            if (!$defaultValue || $defaultValue === "null") {
                $defaultValue = $regionCollection->getFirstItem()->getData('region_id');
            }
        }

        return $defaultValue;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return (string)$this->getValue(self::GENERAL_BLOCK . 'title');
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return (string)$this->getValue(self::GENERAL_BLOCK . 'description');
    }

    /**
     * @param string $setting
     *
     * @return bool|string
     */
    public function getRgbSetting($setting)
    {
        $code = (string)$this->getValue($setting);
        $code = trim($code);

        if (!preg_match('|#[0-9a-fA-F]{3,6}|', $code)) {
            return false;
        }

        return $code;
    }

    /**
     * @return string
     */
    public function getCustomFont(): string
    {
        return (string)$this->getValue(self::DESIGN_BLOCK . 'font');
    }

    /**
     * @return string
     */
    public function getHeaderFooter()
    {
        return $this->getValue(self::DESIGN_BLOCK . 'header_footer');
    }

    /**
     * @param string $value
     */
    public function saveTelephoneOption($value)
    {
        $this->configWriter->save('customer/address/telephone_show', $value);
        $this->reinitableConfig->reinit();
        $this->clean();
    }

    /**
     * @return bool
     */
    public function isJsBundleEnabled()
    {
        return $this->isSetFlag(self::GENERAL_BLOCK . 'bundling');
    }

    public function isEditTypeAutomatically(int $storeId = null): bool
    {
        return (bool)$this->getValue(self::GENERAL_BLOCK . 'product_editing_type', $storeId);
    }
}
