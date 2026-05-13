<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package One Step Checkout Layout Builder for Magento 2 (System)
 */

namespace Amasty\CheckoutLayoutBuilder\Setup\Patch\Data\Utils;

use Amasty\Base\Model\Serializer;
use Amasty\CheckoutCore\Model\Config\Source\Layout;
use Amasty\CheckoutLayoutBuilder\Model\ConfigProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class ConfigManager
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Serializer
     */
    private $serializer;

    public function __construct(StoreManagerInterface $storeManager, Serializer $serializer)
    {
        $this->storeManager = $storeManager;
        $this->serializer = $serializer;
    }

    /**
     * Transform db rows array to scoped config
     * @param array $configRows
     * @return array ['scopeCode_scopeId'=>['field1'=>'value1','field2'=>'value2'],'otherScopeCode_otherScopeId'=>[...]]
     */
    public function reorderConfigRowsToScopedConfig(array $configRows): array
    {
        $blockNamesBlockPath = ConfigProvider::PATH_PREFIX . 'block_names/';
        $designBlockPath = ConfigProvider::PATH_PREFIX . ConfigProvider::DESIGN_BLOCK;

        $scopedConfig = [];
        foreach ($configRows as $configRow) {
            $scopeKey = $configRow['scope'] . '_' . $configRow['scope_id'];
            if (strpos($configRow['path'], $blockNamesBlockPath) !== false) {
                $blockName = str_replace($blockNamesBlockPath, '', $configRow['path']);
                $blockName = $this->getNewBlockName($blockName);
                if ($blockName == 'management') {
                    continue;
                }
                $scopedConfig[$scopeKey]['blockConfig'][$blockName] = $this->serializer->unserialize(
                    $configRow['value']
                );
            } else {
                $configName = str_replace($designBlockPath, '', $configRow['path']);
                $scopedConfig[$scopeKey][$configName] = $configRow['value'];
            }
        }

        return $scopedConfig;
    }

    /**
     * Sort scoped config by priority (default scope is first, then website scope and stores is last.
     * It requires for getting unspecified value from parent config in next algorithms.
     *
     * @param array $scopedConfig
     * @return array
     */
    public function sortConfigGlobalScopeFirst(array $scopedConfig): array
    {
        $scopeToPriority = [
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT => 0,
            ScopeInterface::SCOPE_WEBSITES => 1,
            ScopeInterface::SCOPE_STORES => 2,
        ];

        uksort($scopedConfig, function ($scopeKey1, $scopeKey2) use ($scopeToPriority) {
            list($scope1, ) = explode('_', $scopeKey1);
            list($scope2, ) = explode('_', $scopeKey2);

            $scope1Priority = $scopeToPriority[$scope1];
            $scope2Priority = $scopeToPriority[$scope2];

            return $scope1Priority <=> $scope2Priority;
        });

        return $scopedConfig;
    }

    /**
     * @return array
     */
    public function getDefaultValues(): array
    {
        return [
            ConfigProvider::FIELD_CHECKOUT_DESIGN => '0',
            ConfigProvider::FIELD_CHECKOUT_LAYOUT => Layout::TWO_COLUMNS,
            ConfigProvider::FIELD_CHECKOUT_LAYOUT_MODERN => Layout::THREE_COLUMNS,
            'blockConfig' => [
                'shipping_address' => ['sort_order' => 1, 'value' => ''],
                'shipping_method' => ['sort_order' => 2, 'value' => ''],
                'payment_method' => ['sort_order' => 3, 'value' => ''],
                'summary' => ['sort_order' => 4, 'value' => ''],
            ],
        ];
    }

    /**
     * @param string $fieldName
     * @return mixed|null
     */
    public function getDefaultValue(string $fieldName)
    {
        return $this->getDefaultValues()[$fieldName] ?? null;
    }

    /**
     * @param array $scopedConfig
     * @param string $scope
     * @param string $scopeId
     * @param string $field
     * @return mixed|null
     */
    public function getParentScopeValue($scopedConfig, $scope, $scopeId, $field)
    {
        $value = null;
        switch ($scope) {
            case ScopeInterface::SCOPE_STORES:
                $parentScope = ScopeInterface::SCOPE_WEBSITES;
                try {
                    $parentScopeId = $this->storeManager->getStore($scopeId)->getWebsiteId();
                } catch (NoSuchEntityException $e) {
                    return null;
                }
                break;
            case ScopeInterface::SCOPE_WEBSITES:
                $parentScope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
                $parentScopeId = 0;
                break;
            default:
                return null;
        }

        $scopeKey = $parentScope . '_' . $parentScopeId;

        if (isset($scopedConfig[$scopeKey][$field])) {
            $value = $scopedConfig[$scopeKey][$field];
        } else {
            $value = $this->getParentScopeValue(
                $scopedConfig,
                $parentScope,
                $parentScopeId,
                $field
            );
        }

        return $value;
    }

    /**
     * @param string $oldBlockName
     * @return string
     */
    private function getNewBlockName(string $oldBlockName): string
    {
        $prefix = 'block_';
        if ($oldBlockName === 'block_order_summary') {
            $prefix .= 'order_';
        }

        return str_replace($prefix, '', $oldBlockName);
    }
}
