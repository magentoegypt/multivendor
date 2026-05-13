<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Plugin\Checkout\Block\Checkout;

use Amasty\CheckoutCore\Model\Field;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Api\CustomAttributesDataInterface;
use Amasty\CheckoutCore\Api\Data\CustomFieldsConfigInterface;
use Amasty\CheckoutCore\Model\FieldsDefaultProvider;
use Amasty\CheckoutCore\Model\Config as ConfigProvider;

/**
 * AttributeMerger plugin class.
 * Add configuration from "Manage Checkout Fields" into attributes fields on checkout and cart pages
 */
class AttributeMerger
{
    /**
     * @var Field[]|null
     */
    private $fieldsConfig;

    /**
     * @var Field
     */
    private $fieldSingleton;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var LayoutProcessor
     */
    private $layoutProcessorPlugin;

    /**
     * @var FieldsDefaultProvider
     */
    private $fieldsDefaultProvider;

    /**
     * @var ConfigProvider
     */
    private $checkoutConfig;

    /**
     * @var LayoutInterface
     */
    private $layout;

    public function __construct(
        Field $fieldSingleton,
        StoreManagerInterface $storeManager,
        LayoutProcessor $layoutProcessorPlugin,
        FieldsDefaultProvider $fieldsDefaultProvider,
        ConfigProvider $checkoutConfig,
        LayoutInterface $layout
    ) {
        $this->fieldSingleton = $fieldSingleton;
        $this->storeManager = $storeManager;
        $this->layoutProcessorPlugin = $layoutProcessorPlugin;
        $this->fieldsDefaultProvider = $fieldsDefaultProvider;
        $this->checkoutConfig = $checkoutConfig;
        $this->layout = $layout;
    }

    /**
     * @return array
     */
    public function getDefaultData()
    {
        return $this->fieldsDefaultProvider->getDefaultData();
    }

    /**
     * @return Field[]
     */
    public function getFieldConfig()
    {
        if ($this->fieldsConfig === null) {
            $this->fieldsConfig = $this->fieldSingleton->getConfig(
                $this->storeManager->getStore()->getId()
            );
        }

        return $this->fieldsConfig;
    }

    /**
     * @param \Magento\Checkout\Block\Checkout\AttributeMerger $subject
     * @param array $elements
     * @param string $providerName name of the storage container used by UI component
     * @param string $dataScopePrefix
     * @param array $fields
     *
     * @return array|null
     * @see \Magento\Checkout\Block\Checkout\AttributeMerger:getFieldConfig to understand wth is going on here
     * @SuppressWarnings(PHPMD.UnusedFormatParameter)
     */
    public function beforeMerge(
        \Magento\Checkout\Block\Checkout\AttributeMerger $subject,
        $elements,
        $providerName,
        $dataScopePrefix,
        array $fields = []
    ) {
        if (!$this->checkoutConfig->isEnabled()) {
            return null;
        }

        $defaultData = $this->getDefaultData();
        $fieldConfig = $this->getFieldConfig();
        $inheritedAttributes = $this->fieldSingleton->getInheritedAttributes();

        foreach ($elements as $attributeCode => &$attributeConfig) {
            if (isset($defaultData[$attributeCode])) {
                $attributeConfig['default'] = $defaultData[$attributeCode];
            }

            if (isset($inheritedAttributes[$attributeCode])) {
                $parent = $inheritedAttributes[$attributeCode];
                $attributeConfig = $this->prepareInheritedAttributeConfig($attributeConfig, $parent, $fieldConfig);
            }

            if (isset($fieldConfig[$attributeCode])) {
                $field = $fieldConfig[$attributeCode];

                if (!(int)$field->getData('enabled')) {
                    unset($elements[$attributeCode]);
                    unset($fields[$attributeCode]);
                    if ($attributeCode === 'region') {
                        unset($elements['region_id']);
                        unset($fields['region_id']);
                    }
                    continue;
                }

                /** @var \Amasty\CheckoutCore\Model\Field $field */
                $this->layoutProcessorPlugin->setOrder($attributeCode, $field->getData('sort_order'));
                $attributeConfig = $this->prepareExtraAttributeConfig($attributeConfig, $field);
            }
        }
        unset($attributeConfig);
        if (isset($elements['telephone'])) {
            $fields['telephone']['config']['elementTmpl'] = 'Amasty_CheckoutCore/form/element/telephone';
            $fields['telephone']['config']['tooltipTpl'] = 'Amasty_CheckoutCore/form/element/tooltip';
        }

        if (in_array('amasty_checkout', $this->layout->getUpdate()->getHandles())) {
            $elements = $this->addCustomFields($fieldConfig, $elements, $dataScopePrefix);
        }

        return [$elements, $providerName, $dataScopePrefix, $fields];
    }

    /**
     * @param \Magento\Checkout\Block\Checkout\AttributeMerger $subject
     * @param array $config
     *
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormatParameter)
     */
    public function afterMerge(\Magento\Checkout\Block\Checkout\AttributeMerger $subject, $config)
    {
        if (!$this->checkoutConfig->isEnabled()) {
            return $config;
        }
        $fieldConfig = $this->getFieldConfig();

        if (isset($config['postcode']) && isset($fieldConfig['postcode'])) {
            $config['postcode']['skipValidation'] = !$fieldConfig['postcode']->getData('required');
        }

        $this->setDefaultRegionData($config);

        foreach ($config as $code => &$configItem) {
            if (isset($fieldConfig[$code])) {
                $configItem['sortOrder'] = $fieldConfig[$code]->getData('sort_order');
            }

            $this->setDeps($configItem);
        }

        return $config;
    }

    /**
     * Fix frontend validation on optimized checkout.
     * On enabled bundling, form elements may initialize before provider.
     */
    private function setDeps(array &$configItem): void
    {
        if (isset($configItem['provider'])) {
            $configItem['config']['deps'][] = $configItem['provider'];
        }
    }

    private function setDefaultRegionData(array &$config): void
    {
        if (isset($config['region_id'])) {
            $defaultData = $this->getDefaultData();
            $config['region_id']['component'] = 'Amasty_CheckoutCore/js/form/element/region';
            if (!empty($defaultData['region_id'])) {
                $config['region_id']['default'] = $defaultData['region_id'];
            }
        }
    }

    /**
     * @param array $fieldConfig
     * @param array $elements
     * @param string $dataScopePrefix
     *
     * @return array
     */
    private function addCustomFields($fieldConfig, $elements, $dataScopePrefix)
    {
        if (!strpos($dataScopePrefix, '.custom_attributes') !== false) {
            $countOfCustomFields = CustomFieldsConfigInterface::COUNT_OF_CUSTOM_FIELDS;
            $index = CustomFieldsConfigInterface::CUSTOM_FIELD_INDEX;

            for (; $index <= $countOfCustomFields; $index++) {
                $customFieldIndex = 'custom_field_' . $index;

                if (isset($fieldConfig[$customFieldIndex]) && $fieldConfig[$customFieldIndex]->getEnabled() == 1) {
                    $field = $fieldConfig[$customFieldIndex];

                    $customAttributeName = CustomAttributesDataInterface::CUSTOM_ATTRIBUTES
                        . '.' . $customFieldIndex;

                    $elements[$customAttributeName] = $field->getData();
                    $elements[$customAttributeName]['visible'] = '1';
                    $elements[$customAttributeName]['formElement'] = 'input';
                    $elements[$customAttributeName]['dataType'] = 'text';
                    $elements[$customAttributeName]['sortOrder'] = $field->getSortOrder();
                    $elements[$customAttributeName]['validation']['required-entry'] = (bool)$field->getRequired();
                }
            }
        }

        return $elements;
    }

    /**
     * @param array $attributeConfig
     * @param string $parent
     * @param array $fieldConfig
     *
     * @return mixed
     */
    private function prepareInheritedAttributeConfig($attributeConfig, $parent, $fieldConfig)
    {
        if (isset($fieldConfig[$parent])) {
            $attributeConfig['sortOrder'] = $fieldConfig[$parent]->getData('sort_order');
            $attributeConfig['visible'] = $fieldConfig[$parent]->getData('enabled');

            if ($fieldConfig[$parent]->getData('label') != $fieldConfig[$parent]->getData('default_label')) {
                $attributeConfig['label'] = $fieldConfig[$parent]->getData('label');
            }
        }

        return $attributeConfig;
    }

    /**
     * @param array $attributeConfig
     * @param \Amasty\CheckoutCore\Model\Field $field
     *
     * @return mixed
     */
    private function prepareExtraAttributeConfig($attributeConfig, $field)
    {
        $attributeConfig['sortOrder'] = $field->getData('sort_order');

        if (!isset($attributeConfig['visible']) || $attributeConfig['visible'] !== false) {
            $attributeConfig['visible'] = $field->getData('enabled');
        }

        $attributeConfig['required'] = $field->getData('required');
        $attributeConfig['validation']['required-entry'] = (bool)$field->getData('required');

        $label = $field->getData('label');

        if ($label != $field->getData('default_label')) {
            $attributeConfig['label'] = $label;
        }

        return $attributeConfig;
    }
}
