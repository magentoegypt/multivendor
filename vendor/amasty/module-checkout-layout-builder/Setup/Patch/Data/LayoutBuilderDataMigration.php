<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package One Step Checkout Layout Builder for Magento 2 (System)
 */

namespace Amasty\CheckoutLayoutBuilder\Setup\Patch\Data;

use Amasty\Base\Model\Serializer;
use Amasty\CheckoutCore\Model\Config\Source\Layout;
use Amasty\CheckoutLayoutBuilder\Model\ConfigProvider;
use Amasty\CheckoutLayoutBuilder\Setup\Patch\Data\Utils\ConfigLoader;
use Magento\Framework\App\Cache\Manager;
use Magento\Framework\App\Cache\Type\Config as CacheTypeConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class LayoutBuilderDataMigration implements DataPatchInterface
{
    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var ConfigLoader
     */
    private $configLoader;

    /**
     * @var Manager
     */
    private $cacheManager;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    public function __construct(
        Serializer $serializer,
        ConfigLoader $configLoader,
        Manager $cacheManager,
        ResourceConnection $resourceConnection
    ) {
        $this->serializer = $serializer;
        $this->configLoader = $configLoader;
        $this->cacheManager = $cacheManager;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @return void
     */
    public function apply()
    {
        $connection = $this->resourceConnection->getConnection();
        $configTable = $this->resourceConnection->getTableName('core_config_data');

        $builderConfigPath = ConfigProvider::PATH_PREFIX
            . ConfigProvider::LAYOUT_BUILDER_BLOCK
            . ConfigProvider::FIELD_LAYOUT_BUILDER_CONFIG;

        $frontendConfigPath = ConfigProvider::PATH_PREFIX
            . ConfigProvider::LAYOUT_BUILDER_BLOCK
            . ConfigProvider::FIELD_FRONTEND_LAYOUT_CONFIG;

        if ($this->configLoader->isExistBuilder($connection, $configTable, $builderConfigPath, $frontendConfigPath)) {
            return;
        }

        $scopedConfig = $this->configLoader->loadConfig($connection, $configTable);
        foreach ($scopedConfig as $scopeKey => $scopeValues) {
            [$scope, $scopeId] = explode('_', $scopeKey);
            [$layoutBuilderConfig, $frontendConfig] = $this->getNewConfig($scopeValues, $scope, $scopeId);

            try {
                $connection->insert(
                    $configTable,
                    [
                        'scope' => $scope,
                        'scope_id' => $scopeId,
                        'path' => $builderConfigPath,
                        'value' => $this->serializer->serialize($layoutBuilderConfig),
                    ]
                );

                $connection->insert(
                    $configTable,
                    [
                        'scope' => $scope,
                        'scope_id' => $scopeId,
                        'path' => $frontendConfigPath,
                        'value' => $this->serializer->serialize($frontendConfig),
                    ]
                );
            } catch (\Exception $exception) {
                unset($exception);
                continue;
            }
        }

        $this->cacheManager->clean([CacheTypeConfig::TYPE_IDENTIFIER]);
    }

    /**
     * @param array $config
     * @param string $scope
     * @param string $scopeId
     * @return array
     */
    private function getNewConfig(array $config, $scope, $scopeId)
    {
        $design = 'classic';
        $layout = $config[ConfigProvider::FIELD_CHECKOUT_LAYOUT];

        if ($config[ConfigProvider::FIELD_CHECKOUT_DESIGN] !== null
            && (int)$config[ConfigProvider::FIELD_CHECKOUT_DESIGN] === 1
        ) {
            $design = 'modern';
            $layout = $config[ConfigProvider::FIELD_CHECKOUT_LAYOUT_MODERN];
        }

        $layoutBuilderConfig = $this->getDefaultPreset();

        // if design=classic and layout set to 1column (for example), we need to change layout, because preset doesn't
        // exist
        if (!isset($layoutBuilderConfig[$design][$layout])) {
            $layout = Layout::THREE_COLUMNS;
        }

        $presetForCurrentConfig = &$layoutBuilderConfig[$design][$layout];

        // summary always on bottom in this case
        if ($design == 'modern' && $layout == Layout::TWO_COLUMNS) {
            $config['blockConfig']['summary']['sort_order'] = 999999999;
        }

        uasort($config['blockConfig'], function ($itemA, $itemB) {
            return $itemA['sort_order'] <=> $itemB['sort_order'];
        });

        // we get array only with x and y from preset, and then fill $configWithXAndY with pairs
        // blockName => ['x' => 'xValue', 'y' => 'yValue', 'title' => 'blockTitle']
        $layoutTemplate = $this->getLayoutTemplateFromPreset($presetForCurrentConfig['layout']);
        $configWithXAndY = [];
        foreach ($config['blockConfig'] as $blockName => $blockConfig) {
            $configWithXAndY[$blockName] = array_shift($layoutTemplate);
            $configWithXAndY[$blockName]['title'] = $blockConfig['value'];
        }

        // then we change $layoutBuilderConfig with new x and y for blocks
        // and form the $frontendConfig as two-dimensional array, where first dimension is column and second is row
        // example for three columns:
        // [[firstColumnFirstBlockData, firstColumnSecondBlockData], [secondColumnBlockData], [thirdColumnBlockData]]
        $frontendConfig = [];
        foreach ($presetForCurrentConfig['layout'] as &$blockData) {
            if (!isset($configWithXAndY[$blockData['i']])) {
                continue;
            }

            $configForCurrentBlock = $configWithXAndY[$blockData['i']];
            if (!isset($configForCurrentBlock['x']) || !isset($configForCurrentBlock['y'])) {
                continue;
            }

            $blockData['x'] = $configForCurrentBlock['x'];
            $blockData['y'] = $configForCurrentBlock['y'];

            // firstly we specified the strong keys
            $frontendConfig[$blockData['x']][$blockData['y']] = [
                'name' => $blockData['i'],
                'title' =>  $configForCurrentBlock['title']
            ];
        }

        // and then sort by that keys
        $frontendConfig = $this->prepareFrontendConfig($frontendConfig);

        return [$layoutBuilderConfig, $frontendConfig];
    }

    /**
     * @param array $frontendConfig
     * @return array
     */
    private function prepareFrontendConfig($frontendConfig)
    {
        ksort($frontendConfig);
        $frontendConfig = array_values($frontendConfig);
        foreach ($frontendConfig as &$item) {
            ksort($item);
            $item = array_values($item);
        }

        return $frontendConfig;
    }

    /**
     * @param array $presetLayout
     * @return array
     */
    private function getLayoutTemplateFromPreset($presetLayout)
    {
        $layoutTemplate = [];
        foreach ($presetLayout as $item) {
            $layoutTemplate[] = [
                'x' => $item['x'],
                'y' => $item['y']
            ];
        }

        return $layoutTemplate;
    }

    /**
     * @return array
     */
    private function getDefaultPreset()
    {
        return [
            'classic' => [
                Layout::TWO_COLUMNS => [
                    'frontendColumns' => 2,
                    'columnsWidth' => [0 => 1, 1 => 1,],
                    'axis' => 'both',
                    'cols' => 2,
                    'layout' => [
                        0 => [
                            'i' => 'shipping_address',
                            'x' => 0,
                            'y' => 0,
                            'w' => 1,
                            'h' => 1,
                        ],
                        1 => [
                            'i' => 'shipping_method',
                            'x' => 0,
                            'y' => 1,
                            'w' => 1,
                            'h' => 1,
                        ],
//                        2 => [
//                            'i' => 'delivery',
//                            'x' => 0,
//                            'y' => 2,
//                            'w' => 1,
//                            'h' => 1,
//                        ],
                        2 => [
                            'i' => 'payment_method',
                            'x' => 1,
                            'y' => 0,
                            'w' => 1,
                            'h' => 1,
                        ],
                        3 => [
                            'i' => 'summary',
                            'x' => 1,
                            'y' => 1,
                            'w' => 1,
                            'h' => 1,
                        ],
                    ],
                ],
                Layout::THREE_COLUMNS => [
                    'frontendColumns' => 3,
                    'columnsWidth' => [0 => 1, 1 => 1, 2 => 1,],
                    'axis' => 'both',
                    'cols' => 3,
                    'layout' => [
                        0 => [
                            'i' => 'shipping_address',
                            'x' => 0,
                            'y' => 0,
                            'w' => 1,
                            'h' => 1,
                        ],
                        1 => [
                            'i' => 'shipping_method',
                            'x' => 1,
                            'y' => 0,
                            'w' => 1,
                            'h' => 1,
                        ],
//                        2 => [
//                            'i' => 'delivery',
//                            'x' => 1,
//                            'y' => 2,
//                            'w' => 1,
//                            'h' => 1,
//                        ],
                        2 => [
                            'i' => 'payment_method',
                            'x' => 1,
                            'y' => 3,
                            'w' => 1,
                            'h' => 1,
                        ],
                        3 => [
                            'i' => 'summary',
                            'x' => 2,
                            'y' => 0,
                            'w' => 1,
                            'h' => 1,
                        ],
                    ],
                ],
            ],
            'modern' => [
                Layout::ONE_COLUMN => [
                    'frontendColumns' => 1,
                    'columnsWidth' => [0 => 1,],
                    'axis' => 'both',
                    'cols' => 1,
                    'layout' => [
                        0 => [
                            'i' => 'shipping_address',
                            'x' => 0,
                            'y' => 0,
                            'w' => 1,
                            'h' => 1,
                        ],
                        1 => [
                            'i' => 'shipping_method',
                            'x' => 0,
                            'y' => 1,
                            'w' => 1,
                            'h' => 1,
                        ],
//                        2 => [
//                            'i' => 'delivery',
//                            'x' => 0,
//                            'y' => 2,
//                            'w' => 1,
//                            'h' => 1,
//                        ],
                        2 => [
                            'i' => 'payment_method',
                            'x' => 0,
                            'y' => 3,
                            'w' => 1,
                            'h' => 1,
                        ],
                        3 => [
                            'i' => 'summary',
                            'x' => 0,
                            'y' => 4,
                            'w' => 1,
                            'h' => 1,
                        ],
                    ],
                ],
                Layout::TWO_COLUMNS => [
                    'frontendColumns' => 2,
                    'columnsWidth' => [0 => 2, 1 => 1,],
                    'axis' => 'y',
                    'cols' => 3,
                    'layout' => [
                        0 => [
                            'i' => 'shipping_address',
                            'x' => 0,
                            'y' => 0,
                            'w' => 2,
                            'h' => 1,
                        ],
                        1 => [
                            'i' => 'shipping_method',
                            'x' => 0,
                            'y' => 1,
                            'w' => 2,
                            'h' => 1,
                        ],
//                        2 => [
//                            'i' => 'delivery',
//                            'x' => 0,
//                            'y' => 2,
//                            'w' => 2,
//                            'h' => 1,
//                        ],
                        2 => [
                            'i' => 'payment_method',
                            'x' => 0,
                            'y' => 3,
                            'w' => 2,
                            'h' => 1,
                        ],
                        3 => [
                            'i' => 'summary',
                            'x' => 2,
                            'y' => 0,
                            'w' => 1,
                            'h' => 1,
                            'static' => true,
                            'axis' => 'x',
                        ],
                    ],
                ],
                Layout::THREE_COLUMNS => [
                    'frontendColumns' => 3,
                    'columnsWidth' => [0 => 1, 1 => 1, 2 => 1,],
                    'axis' => 'both',
                    'cols' => 3,
                    'layout' => [
                        0 => [
                            'i' => 'shipping_address',
                            'x' => 0,
                            'y' => 0,
                            'w' => 1,
                            'h' => 1,
                        ],
                        1 => [
                            'i' => 'shipping_method',
                            'x' => 1,
                            'y' => 0,
                            'w' => 1,
                            'h' => 1,
                        ],
//                        2 => [
//                            'i' => 'delivery',
//                            'x' => 1,
//                            'y' => 1,
//                            'w' => 1,
//                            'h' => 1,
//                        ],
                        2 => [
                            'i' => 'payment_method',
                            'x' => 1,
                            'y' => 2,
                            'w' => 1,
                            'h' => 1,
                        ],
                        3 => [
                            'i' => 'summary',
                            'x' => 2,
                            'y' => 0,
                            'w' => 1,
                            'h' => 1,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array|string[]
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies()
    {
        return [];
    }
}
