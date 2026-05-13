<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package One Step Checkout Layout Builder for Magento 2 (System)
 */

namespace Amasty\CheckoutLayoutBuilder\Test\Integration\Setup\Operation;

use Amasty\CheckoutCore\Model\Config\Source\Layout;
use Amasty\CheckoutLayoutBuilder\Model\ConfigProvider;
use Amasty\CheckoutLayoutBuilder\Setup\Patch\Data\LayoutBuilderDataMigration;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Class LayoutBuilderDataMigrationTest
 *
 * @see LayoutBuilderDataMigration
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * phpcs:ignoreFile
 */
class LayoutBuilderDataMigrationTest extends TestCase
{
    public const CLASSIC_DESIGN = '0';
    public const MODERN_DESIGN = '1';

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private $connection;

    /**
     * @var string
     */
    private $configTableName;


    public function setUp(): void
    {
        /** @var ModuleDataSetupInterface $moduleDataSetup */
        $this->moduleDataSetup = Bootstrap::getObjectManager()->get(ModuleDataSetupInterface::class);

        $this->connection = $this->moduleDataSetup->getConnection();
        $this->configTableName = $this->moduleDataSetup->getTable('core_config_data');
    }

    /**
     * @covers \Amasty\CheckoutLayoutBuilder\Setup\Patch\Data\LayoutBuilderDataMigration::apply
     * @dataProvider testExecuteDataProvider
     * @magentoDataFixture Magento/Store/_files/core_fixturestore.php
     * @param array $configFixtures
     * @param array $results
     */
    public function testExecute($configFixtures, $results)
    {
        // backup old values, then drop them and save new values
        $oldValuesFixtures = $this->loadValues($configFixtures);
        $oldValuesResults = $this->loadValues($results);
        $this->clearValues($configFixtures);
        $this->clearValues($results);
        $this->saveValues($configFixtures);

        /** @var LayoutBuilderDataMigration $layoutBuilderDataMigration */
        $layoutBuilderDataMigration = Bootstrap::getObjectManager()->get(
            LayoutBuilderDataMigration::class
        );

        $layoutBuilderDataMigration->apply();

        foreach ($results as $resultRow) {
            $select = $this->connection
                ->select()
                ->from($this->configTableName, ['value'])
                ->where('path = ?', $resultRow['path'])
                ->where('scope = ?', $resultRow['scope'])
                ->where('scope_id = ?', $resultRow['scope_id']);
            $valueFromDb = $this->connection->fetchOne($select);
            $this->assertEquals($resultRow['value'], $valueFromDb);
        }

        // drop new values and then save the old values
        $this->clearValues($configFixtures);
        $this->clearValues($results);
        $this->saveValues($oldValuesFixtures);
        $this->saveValues($oldValuesResults);
    }

    /**
     * @param array $configRows
     * @return array
     */
    private function loadValues($configRows)
    {
        $values = $configRows;
        foreach ($values as &$configRow) {
            $select = $this->connection
                ->select()
                ->from($this->configTableName, ['value'])
                ->where('path = ?', $configRow['path'])
                ->where('scope = ?', $configRow['scope'])
                ->where('scope_id = ?', $configRow['scope_id']);
            $configRow['value'] = $this->connection->fetchOne($select);
        }

        return $values;
    }

    /**
     * @param array $configRows
     */
    private function clearValues($configRows)
    {
        foreach ($configRows as $configRow) {
            $this->connection->delete(
                $this->configTableName,
                [
                    'path = ?' => $configRow['path'],
                    'scope = ?' => $configRow['scope'],
                    'scope_id = ?' => $configRow['scope_id']
                ]
            );
        }
    }

    /**
     * @param array $configRows
     */
    private function saveValues($configRows)
    {
        if (empty($configRows)) {
            return;
        }
        $this->connection->insertMultiple(
            $this->configTableName,
            $configRows
        );
    }

    public function testExecuteDataProvider()
    {
        /** @var \Magento\Store\Model\Store $store */
        $store = Bootstrap::getObjectManager()->create(\Magento\Store\Model\Store::class);
        $storeCode = 'fixturestore';
        $store->load($storeCode);
        $storeId = $store->getId();
        $websiteId = $store->getWebsiteId();

        $blockNamesBlockPath = ConfigProvider::PATH_PREFIX . 'block_names/';
        $designBlockPath = ConfigProvider::PATH_PREFIX . ConfigProvider::DESIGN_BLOCK;
        $layoutBuilderBlockPath = ConfigProvider::PATH_PREFIX . ConfigProvider::LAYOUT_BUILDER_BLOCK;
        return [

            'emptyConfig' => [
                [
                ],

                [
                    [
                        'scope' => ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        'scope_id' => 0,
                        'path' => $layoutBuilderBlockPath . ConfigProvider::FIELD_FRONTEND_LAYOUT_CONFIG,
                        'value' => '[[{"name":"shipping_address","title":""},{"name":"shipping_method","title":""}],[{"name":"payment_method","title":""},{"name":"summary","title":""}]]',
                    ],

                    [
                        'scope' => ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        'scope_id' => 0,
                        'path' => $layoutBuilderBlockPath . ConfigProvider::FIELD_LAYOUT_BUILDER_CONFIG,
                        'value' => '{"classic":{"2columns":{"frontendColumns":2,"columnsWidth":[1,1],"axis":"both","cols":2,"layout":[{"i":"shipping_address","x":0,"y":0,"w":1,"h":1},{"i":"shipping_method","x":0,"y":1,"w":1,"h":1},{"i":"payment_method","x":1,"y":0,"w":1,"h":1},{"i":"summary","x":1,"y":1,"w":1,"h":1}]},"3columns":{"frontendColumns":3,"columnsWidth":[1,1,1],"axis":"both","cols":3,"layout":[{"i":"shipping_address","x":0,"y":0,"w":1,"h":1},{"i":"shipping_method","x":1,"y":0,"w":1,"h":1},{"i":"payment_method","x":1,"y":3,"w":1,"h":1},{"i":"summary","x":2,"y":0,"w":1,"h":1}]}},"modern":{"1column":{"frontendColumns":1,"columnsWidth":[1],"axis":"both","cols":1,"layout":[{"i":"shipping_address","x":0,"y":0,"w":1,"h":1},{"i":"shipping_method","x":0,"y":1,"w":1,"h":1},{"i":"payment_method","x":0,"y":3,"w":1,"h":1},{"i":"summary","x":0,"y":4,"w":1,"h":1}]},"2columns":{"frontendColumns":2,"columnsWidth":[2,1],"axis":"y","cols":3,"layout":[{"i":"shipping_address","x":0,"y":0,"w":2,"h":1},{"i":"shipping_method","x":0,"y":1,"w":2,"h":1},{"i":"payment_method","x":0,"y":3,"w":2,"h":1},{"i":"summary","x":2,"y":0,"w":1,"h":1,"static":true,"axis":"x"}]},"3columns":{"frontendColumns":3,"columnsWidth":[1,1,1],"axis":"both","cols":3,"layout":[{"i":"shipping_address","x":0,"y":0,"w":1,"h":1},{"i":"shipping_method","x":1,"y":0,"w":1,"h":1},{"i":"payment_method","x":1,"y":2,"w":1,"h":1},{"i":"summary","x":2,"y":0,"w":1,"h":1}]}}}',
                    ]
                ]
            ],

            'defaultConfigWithClassic3Columns' => [
                [
                    [
                        'scope' => ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        'scope_id' => 0,
                        'path' => $designBlockPath . ConfigProvider::FIELD_CHECKOUT_DESIGN,
                        'value' => self::CLASSIC_DESIGN,
                    ],
                    [
                        'scope' => ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        'scope_id' => 0,
                        'path' => $designBlockPath . ConfigProvider::FIELD_CHECKOUT_LAYOUT,
                        'value' => Layout::THREE_COLUMNS,
                    ],
                    [
                        'scope' => ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        'scope_id' => 0,
                        'path' => $blockNamesBlockPath . 'block_management',
                        'value' => '{"inherit":"0"}',
                    ],
                    [
                        'scope' => ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        'scope_id' => 0,
                        'path' => $blockNamesBlockPath . 'block_shipping_address',
                        'value' => '{"sort_order":"0","value":"ship addres"}',
                    ],
                    [
                        'scope' => ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        'scope_id' => 0,
                        'path' => $blockNamesBlockPath . 'block_shipping_method',
                        'value' => '{"sort_order":"1","value":""}',
                    ],
//                    [
//                        'scope' => ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
//                        'scope_id' => 0,
//                        'path' => $blockNamesBlockPath . 'block_delivery',
//                        'value' => '{"sort_order":"2","value":""}',
//                    ],
                    [
                        'scope' => ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        'scope_id' => 0,
                        'path' => $blockNamesBlockPath . 'block_payment_method',
                        'value' => '{"sort_order":"3","value":""}',
                    ],
                    [
                        'scope' => ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        'scope_id' => 0,
                        'path' => $blockNamesBlockPath . 'block_order_summary',
                        'value' => '{"sort_order":"4","value":""}',
                    ],

                ],

                [
                    [
                        'scope' => ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        'scope_id' => 0,
                        'path' => $layoutBuilderBlockPath . ConfigProvider::FIELD_FRONTEND_LAYOUT_CONFIG,
                        'value' => '[[{"name":"shipping_address","title":"ship addres"}],[{"name":"shipping_method","title":""},{"name":"payment_method","title":""}],[{"name":"summary","title":""}]]',
                    ],

                    [
                        'scope' => ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        'scope_id' => 0,
                        'path' => $layoutBuilderBlockPath . ConfigProvider::FIELD_LAYOUT_BUILDER_CONFIG,
                        'value' => '{"classic":{"2columns":{"frontendColumns":2,"columnsWidth":[1,1],"axis":"both","cols":2,"layout":[{"i":"shipping_address","x":0,"y":0,"w":1,"h":1},{"i":"shipping_method","x":0,"y":1,"w":1,"h":1},{"i":"payment_method","x":1,"y":0,"w":1,"h":1},{"i":"summary","x":1,"y":1,"w":1,"h":1}]},"3columns":{"frontendColumns":3,"columnsWidth":[1,1,1],"axis":"both","cols":3,"layout":[{"i":"shipping_address","x":0,"y":0,"w":1,"h":1},{"i":"shipping_method","x":1,"y":0,"w":1,"h":1},{"i":"payment_method","x":1,"y":3,"w":1,"h":1},{"i":"summary","x":2,"y":0,"w":1,"h":1}]}},"modern":{"1column":{"frontendColumns":1,"columnsWidth":[1],"axis":"both","cols":1,"layout":[{"i":"shipping_address","x":0,"y":0,"w":1,"h":1},{"i":"shipping_method","x":0,"y":1,"w":1,"h":1},{"i":"payment_method","x":0,"y":3,"w":1,"h":1},{"i":"summary","x":0,"y":4,"w":1,"h":1}]},"2columns":{"frontendColumns":2,"columnsWidth":[2,1],"axis":"y","cols":3,"layout":[{"i":"shipping_address","x":0,"y":0,"w":2,"h":1},{"i":"shipping_method","x":0,"y":1,"w":2,"h":1},{"i":"payment_method","x":0,"y":3,"w":2,"h":1},{"i":"summary","x":2,"y":0,"w":1,"h":1,"static":true,"axis":"x"}]},"3columns":{"frontendColumns":3,"columnsWidth":[1,1,1],"axis":"both","cols":3,"layout":[{"i":"shipping_address","x":0,"y":0,"w":1,"h":1},{"i":"shipping_method","x":1,"y":0,"w":1,"h":1},{"i":"payment_method","x":1,"y":2,"w":1,"h":1},{"i":"summary","x":2,"y":0,"w":1,"h":1}]}}}',
                    ]
                ]
            ],

            'defaultWebsiteAndStoreConfig' => [
                [
                    // default configs - classic 3 columns
                    [
                        'scope' => ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        'scope_id' => 0,
                        'path' => $designBlockPath . ConfigProvider::FIELD_CHECKOUT_DESIGN,
                        'value' => self::CLASSIC_DESIGN,
                    ],
                    [
                        'scope' => ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        'scope_id' => 0,
                        'path' => $designBlockPath . ConfigProvider::FIELD_CHECKOUT_LAYOUT,
                        'value' => Layout::TWO_COLUMNS,
                    ],
                    [
                        'scope' => ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        'scope_id' => 0,
                        'path' => $blockNamesBlockPath . 'block_management',
                        'value' => '{"inherit":"0"}',
                    ],
                    [
                        'scope' => ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        'scope_id' => 0,
                        'path' => $blockNamesBlockPath . 'block_shipping_address',
                        'value' => '{"sort_order":"4","value":"shipppppping addres"}',
                    ],
                    [
                        'scope' => ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        'scope_id' => 0,
                        'path' => $blockNamesBlockPath . 'block_shipping_method',
                        'value' => '{"sort_order":"0","value":"Meethod"}',
                    ],
//                    [
//                        'scope' => ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
//                        'scope_id' => 0,
//                        'path' => $blockNamesBlockPath . 'block_delivery',
//                        'value' => '{"sort_order":"1","value":"DDelivery"}',
//                    ],
                    [
                        'scope' => ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        'scope_id' => 0,
                        'path' => $blockNamesBlockPath . 'block_payment_method',
                        'value' => '{"sort_order":"3","value":"Pay Here"}',
                    ],
                    [
                        'scope' => ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        'scope_id' => 0,
                        'path' => $blockNamesBlockPath . 'block_order_summary',
                        'value' => '{"sort_order":"2","value":"Summary"}',
                    ],

                    // website config modern one column
                    [
                        'scope' => ScopeInterface::SCOPE_WEBSITES,
                        'scope_id' => $websiteId,
                        'path' => $designBlockPath . ConfigProvider::FIELD_CHECKOUT_DESIGN,
                        'value' => self::MODERN_DESIGN,
                    ],
                    [
                        'scope' => ScopeInterface::SCOPE_WEBSITES,
                        'scope_id' => $websiteId,
                        'path' => $designBlockPath . ConfigProvider::FIELD_CHECKOUT_LAYOUT_MODERN,
                        'value' => Layout::ONE_COLUMN,
                    ],
                    [
                        'scope' => ScopeInterface::SCOPE_WEBSITES,
                        'scope_id' => $websiteId,
                        'path' => $blockNamesBlockPath . 'block_management',
                        'value' => '{"inherit":"0"}',
                    ],
                    [
                        'scope' => ScopeInterface::SCOPE_WEBSITES,
                        'scope_id' => $websiteId,
                        'path' => $blockNamesBlockPath . 'block_shipping_address',
                        'value' => '{"sort_order":"3","value":""}',
                    ],
                    [
                        'scope' => ScopeInterface::SCOPE_WEBSITES,
                        'scope_id' => $websiteId,
                        'path' => $blockNamesBlockPath . 'block_shipping_method',
                        'value' => '{"sort_order":"4","value":""}',
                    ],
//                    [
//                        'scope' => ScopeInterface::SCOPE_WEBSITES,
//                        'scope_id' => $websiteId,
//                        'path' => $blockNamesBlockPath . 'block_delivery',
//                        'value' => '{"sort_order":"2","value":""}',
//                    ],
                    [
                        'scope' => ScopeInterface::SCOPE_WEBSITES,
                        'scope_id' => $websiteId,
                        'path' => $blockNamesBlockPath . 'block_payment_method',
                        'value' => '{"sort_order":"1","value":""}',
                    ],
                    [
                        'scope' => ScopeInterface::SCOPE_WEBSITES,
                        'scope_id' => $websiteId,
                        'path' => $blockNamesBlockPath . 'block_order_summary',
                        'value' => '{"sort_order":"0","value":""}',
                    ],

                    // store config modern two columns
                    [
                        'scope' => ScopeInterface::SCOPE_STORES,
                        'scope_id' => $storeId,
                        'path' => $designBlockPath . ConfigProvider::FIELD_CHECKOUT_DESIGN,
                        'value' => self::MODERN_DESIGN,
                    ],
                    [
                        'scope' => ScopeInterface::SCOPE_STORES,
                        'scope_id' => $storeId,
                        'path' => $designBlockPath . ConfigProvider::FIELD_CHECKOUT_LAYOUT_MODERN,
                        'value' => Layout::TWO_COLUMNS,
                    ],
                    [
                        'scope' => ScopeInterface::SCOPE_STORES,
                        'scope_id' => $storeId,
                        'path' => $blockNamesBlockPath . 'block_management',
                        'value' => '{"inherit":"0"}',
                    ],
                    [
                        'scope' => ScopeInterface::SCOPE_STORES,
                        'scope_id' => $storeId,
                        'path' => $blockNamesBlockPath . 'block_shipping_address',
                        'value' => '{"sort_order":"3","value":""}',
                    ],
                    [
                        'scope' => ScopeInterface::SCOPE_STORES,
                        'scope_id' => $storeId,
                        'path' => $blockNamesBlockPath . 'block_shipping_method',
                        'value' => '{"sort_order":"4","value":""}',
                    ],
//                    [
//                        'scope' => ScopeInterface::SCOPE_STORES,
//                        'scope_id' => $storeId,
//                        'path' => $blockNamesBlockPath . 'block_delivery',
//                        'value' => '{"sort_order":"2","value":""}',
//                    ],
                    [
                        'scope' => ScopeInterface::SCOPE_STORES,
                        'scope_id' => $storeId,
                        'path' => $blockNamesBlockPath . 'block_payment_method',
                        'value' => '{"sort_order":"1","value":""}',
                    ],
                    [
                        'scope' => ScopeInterface::SCOPE_STORES,
                        'scope_id' => $storeId,
                        'path' => $blockNamesBlockPath . 'block_order_summary',
                        'value' => '{"sort_order":"","value":""}',
                    ],

                ],

                // result values
                [
                    // default scope
                    [
                        'scope' => ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        'scope_id' => 0,
                        'path' => $layoutBuilderBlockPath . ConfigProvider::FIELD_FRONTEND_LAYOUT_CONFIG,
                        'value' => '[[{"name":"shipping_method","title":"Meethod"},{"name":"summary","title":"Summary"}],[{"name":"payment_method","title":"Pay Here"},{"name":"shipping_address","title":"shipppppping addres"}]]',
                    ],

                    [
                        'scope' => ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        'scope_id' => 0,
                        'path' => $layoutBuilderBlockPath . ConfigProvider::FIELD_LAYOUT_BUILDER_CONFIG,
                        'value' => '{"classic":{"2columns":{"frontendColumns":2,"columnsWidth":[1,1],"axis":"both","cols":2,"layout":[{"i":"shipping_address","x":1,"y":1,"w":1,"h":1},{"i":"shipping_method","x":0,"y":0,"w":1,"h":1},{"i":"payment_method","x":1,"y":0,"w":1,"h":1},{"i":"summary","x":0,"y":1,"w":1,"h":1}]},"3columns":{"frontendColumns":3,"columnsWidth":[1,1,1],"axis":"both","cols":3,"layout":[{"i":"shipping_address","x":0,"y":0,"w":1,"h":1},{"i":"shipping_method","x":1,"y":0,"w":1,"h":1},{"i":"payment_method","x":1,"y":3,"w":1,"h":1},{"i":"summary","x":2,"y":0,"w":1,"h":1}]}},"modern":{"1column":{"frontendColumns":1,"columnsWidth":[1],"axis":"both","cols":1,"layout":[{"i":"shipping_address","x":0,"y":0,"w":1,"h":1},{"i":"shipping_method","x":0,"y":1,"w":1,"h":1},{"i":"payment_method","x":0,"y":3,"w":1,"h":1},{"i":"summary","x":0,"y":4,"w":1,"h":1}]},"2columns":{"frontendColumns":2,"columnsWidth":[2,1],"axis":"y","cols":3,"layout":[{"i":"shipping_address","x":0,"y":0,"w":2,"h":1},{"i":"shipping_method","x":0,"y":1,"w":2,"h":1},{"i":"payment_method","x":0,"y":3,"w":2,"h":1},{"i":"summary","x":2,"y":0,"w":1,"h":1,"static":true,"axis":"x"}]},"3columns":{"frontendColumns":3,"columnsWidth":[1,1,1],"axis":"both","cols":3,"layout":[{"i":"shipping_address","x":0,"y":0,"w":1,"h":1},{"i":"shipping_method","x":1,"y":0,"w":1,"h":1},{"i":"payment_method","x":1,"y":2,"w":1,"h":1},{"i":"summary","x":2,"y":0,"w":1,"h":1}]}}}',
                    ],

                    // website scope
                    [
                        'scope' => ScopeInterface::SCOPE_WEBSITES,
                        'scope_id' => $websiteId,
                        'path' => $layoutBuilderBlockPath . ConfigProvider::FIELD_FRONTEND_LAYOUT_CONFIG,
                        'value' => '[[{"name":"summary","title":""},{"name":"payment_method","title":""},{"name":"shipping_address","title":""},{"name":"shipping_method","title":""}]]',
                    ],

                    [
                        'scope' => ScopeInterface::SCOPE_WEBSITES,
                        'scope_id' => $websiteId,
                        'path' => $layoutBuilderBlockPath . ConfigProvider::FIELD_LAYOUT_BUILDER_CONFIG,
                        'value' => '{"classic":{"2columns":{"frontendColumns":2,"columnsWidth":[1,1],"axis":"both","cols":2,"layout":[{"i":"shipping_address","x":0,"y":0,"w":1,"h":1},{"i":"shipping_method","x":0,"y":1,"w":1,"h":1},{"i":"payment_method","x":1,"y":0,"w":1,"h":1},{"i":"summary","x":1,"y":1,"w":1,"h":1}]},"3columns":{"frontendColumns":3,"columnsWidth":[1,1,1],"axis":"both","cols":3,"layout":[{"i":"shipping_address","x":0,"y":0,"w":1,"h":1},{"i":"shipping_method","x":1,"y":0,"w":1,"h":1},{"i":"payment_method","x":1,"y":3,"w":1,"h":1},{"i":"summary","x":2,"y":0,"w":1,"h":1}]}},"modern":{"1column":{"frontendColumns":1,"columnsWidth":[1],"axis":"both","cols":1,"layout":[{"i":"shipping_address","x":0,"y":3,"w":1,"h":1},{"i":"shipping_method","x":0,"y":4,"w":1,"h":1},{"i":"payment_method","x":0,"y":1,"w":1,"h":1},{"i":"summary","x":0,"y":0,"w":1,"h":1}]},"2columns":{"frontendColumns":2,"columnsWidth":[2,1],"axis":"y","cols":3,"layout":[{"i":"shipping_address","x":0,"y":0,"w":2,"h":1},{"i":"shipping_method","x":0,"y":1,"w":2,"h":1},{"i":"payment_method","x":0,"y":3,"w":2,"h":1},{"i":"summary","x":2,"y":0,"w":1,"h":1,"static":true,"axis":"x"}]},"3columns":{"frontendColumns":3,"columnsWidth":[1,1,1],"axis":"both","cols":3,"layout":[{"i":"shipping_address","x":0,"y":0,"w":1,"h":1},{"i":"shipping_method","x":1,"y":0,"w":1,"h":1},{"i":"payment_method","x":1,"y":2,"w":1,"h":1},{"i":"summary","x":2,"y":0,"w":1,"h":1}]}}}',
                    ],

                    // store scope
                    [
                        'scope' => ScopeInterface::SCOPE_STORES,
                        'scope_id' => $storeId,
                        'path' => $layoutBuilderBlockPath . ConfigProvider::FIELD_FRONTEND_LAYOUT_CONFIG,
                        'value' => '[[{"name":"payment_method","title":""},{"name":"shipping_address","title":""},{"name":"shipping_method","title":""}],[{"name":"summary","title":""}]]',
                    ],

                    [
                        'scope' => ScopeInterface::SCOPE_STORES,
                        'scope_id' => $storeId,
                        'path' => $layoutBuilderBlockPath . ConfigProvider::FIELD_LAYOUT_BUILDER_CONFIG,
                        'value' => '{"classic":{"2columns":{"frontendColumns":2,"columnsWidth":[1,1],"axis":"both","cols":2,"layout":[{"i":"shipping_address","x":0,"y":0,"w":1,"h":1},{"i":"shipping_method","x":0,"y":1,"w":1,"h":1},{"i":"payment_method","x":1,"y":0,"w":1,"h":1},{"i":"summary","x":1,"y":1,"w":1,"h":1}]},"3columns":{"frontendColumns":3,"columnsWidth":[1,1,1],"axis":"both","cols":3,"layout":[{"i":"shipping_address","x":0,"y":0,"w":1,"h":1},{"i":"shipping_method","x":1,"y":0,"w":1,"h":1},{"i":"payment_method","x":1,"y":3,"w":1,"h":1},{"i":"summary","x":2,"y":0,"w":1,"h":1}]}},"modern":{"1column":{"frontendColumns":1,"columnsWidth":[1],"axis":"both","cols":1,"layout":[{"i":"shipping_address","x":0,"y":0,"w":1,"h":1},{"i":"shipping_method","x":0,"y":1,"w":1,"h":1},{"i":"payment_method","x":0,"y":3,"w":1,"h":1},{"i":"summary","x":0,"y":4,"w":1,"h":1}]},"2columns":{"frontendColumns":2,"columnsWidth":[2,1],"axis":"y","cols":3,"layout":[{"i":"shipping_address","x":0,"y":1,"w":2,"h":1},{"i":"shipping_method","x":0,"y":3,"w":2,"h":1},{"i":"payment_method","x":0,"y":0,"w":2,"h":1},{"i":"summary","x":2,"y":0,"w":1,"h":1,"static":true,"axis":"x"}]},"3columns":{"frontendColumns":3,"columnsWidth":[1,1,1],"axis":"both","cols":3,"layout":[{"i":"shipping_address","x":0,"y":0,"w":1,"h":1},{"i":"shipping_method","x":1,"y":0,"w":1,"h":1},{"i":"payment_method","x":1,"y":2,"w":1,"h":1},{"i":"summary","x":2,"y":0,"w":1,"h":1}]}}}',
                    ]
                ]
            ],

            'onlyWebsiteConfigWithModernThemeAndEmptyLayout' => [
                [
                    [
                        'scope' => ScopeInterface::SCOPE_WEBSITES,
                        'scope_id' => $websiteId,
                        'path' => $designBlockPath . ConfigProvider::FIELD_CHECKOUT_DESIGN,
                        'value' => self::MODERN_DESIGN,
                    ],
                    [
                        'scope' => ScopeInterface::SCOPE_WEBSITES,
                        'scope_id' => $websiteId,
                        'path' => $blockNamesBlockPath . 'block_management',
                        'value' => '{"inherit":"0"}',
                    ],
                    [
                        'scope' => ScopeInterface::SCOPE_WEBSITES,
                        'scope_id' => $websiteId,
                        'path' => $blockNamesBlockPath . 'block_shipping_address',
                        'value' => '{"sort_order":"0","value":""}',
                    ],
                    [
                        'scope' => ScopeInterface::SCOPE_WEBSITES,
                        'scope_id' => $websiteId,
                        'path' => $blockNamesBlockPath . 'block_shipping_method',
                        'value' => '{"sort_order":"1","value":""}',
                    ],
//                    [
//                        'scope' => ScopeInterface::SCOPE_WEBSITES,
//                        'scope_id' => $websiteId,
//                        'path' => $blockNamesBlockPath . 'block_delivery',
//                        'value' => '{"sort_order":"2","value":""}',
//                    ],
                    [
                        'scope' => ScopeInterface::SCOPE_WEBSITES,
                        'scope_id' => $websiteId,
                        'path' => $blockNamesBlockPath . 'block_order_summary',
                        'value' => '{"sort_order":"3","value":""}',
                    ],
                    [
                        'scope' => ScopeInterface::SCOPE_WEBSITES,
                        'scope_id' => $websiteId,
                        'path' => $blockNamesBlockPath . 'block_payment_method',
                        'value' => '{"sort_order":"4","value":""}',
                    ],
                ],

                // result values
                [
                    // default scope
                    [
                        'scope' => ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        'scope_id' => 0,
                        'path' => $layoutBuilderBlockPath . ConfigProvider::FIELD_FRONTEND_LAYOUT_CONFIG,
                        'value' => '[[{"name":"shipping_address","title":""},{"name":"shipping_method","title":""}],[{"name":"payment_method","title":""},{"name":"summary","title":""}]]',
                    ],

                    [
                        'scope' => ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        'scope_id' => 0,
                        'path' => $layoutBuilderBlockPath . ConfigProvider::FIELD_LAYOUT_BUILDER_CONFIG,
                        'value' => '{"classic":{"2columns":{"frontendColumns":2,"columnsWidth":[1,1],"axis":"both","cols":2,"layout":[{"i":"shipping_address","x":0,"y":0,"w":1,"h":1},{"i":"shipping_method","x":0,"y":1,"w":1,"h":1},{"i":"payment_method","x":1,"y":0,"w":1,"h":1},{"i":"summary","x":1,"y":1,"w":1,"h":1}]},"3columns":{"frontendColumns":3,"columnsWidth":[1,1,1],"axis":"both","cols":3,"layout":[{"i":"shipping_address","x":0,"y":0,"w":1,"h":1},{"i":"shipping_method","x":1,"y":0,"w":1,"h":1},{"i":"payment_method","x":1,"y":3,"w":1,"h":1},{"i":"summary","x":2,"y":0,"w":1,"h":1}]}},"modern":{"1column":{"frontendColumns":1,"columnsWidth":[1],"axis":"both","cols":1,"layout":[{"i":"shipping_address","x":0,"y":0,"w":1,"h":1},{"i":"shipping_method","x":0,"y":1,"w":1,"h":1},{"i":"payment_method","x":0,"y":3,"w":1,"h":1},{"i":"summary","x":0,"y":4,"w":1,"h":1}]},"2columns":{"frontendColumns":2,"columnsWidth":[2,1],"axis":"y","cols":3,"layout":[{"i":"shipping_address","x":0,"y":0,"w":2,"h":1},{"i":"shipping_method","x":0,"y":1,"w":2,"h":1},{"i":"payment_method","x":0,"y":3,"w":2,"h":1},{"i":"summary","x":2,"y":0,"w":1,"h":1,"static":true,"axis":"x"}]},"3columns":{"frontendColumns":3,"columnsWidth":[1,1,1],"axis":"both","cols":3,"layout":[{"i":"shipping_address","x":0,"y":0,"w":1,"h":1},{"i":"shipping_method","x":1,"y":0,"w":1,"h":1},{"i":"payment_method","x":1,"y":2,"w":1,"h":1},{"i":"summary","x":2,"y":0,"w":1,"h":1}]}}}',
                    ],

                    // website scope
                    [
                        'scope' => ScopeInterface::SCOPE_WEBSITES,
                        'scope_id' => $websiteId,
                        'path' => $layoutBuilderBlockPath . ConfigProvider::FIELD_FRONTEND_LAYOUT_CONFIG,
                        'value' => '[[{"name":"shipping_address","title":""}],[{"name":"shipping_method","title":""},{"name":"summary","title":""}],[{"name":"payment_method","title":""}]]',
                    ],

                    [
                        'scope' => ScopeInterface::SCOPE_WEBSITES,
                        'scope_id' => $websiteId,
                        'path' => $layoutBuilderBlockPath . ConfigProvider::FIELD_LAYOUT_BUILDER_CONFIG,
                        'value' => '{"classic":{"2columns":{"frontendColumns":2,"columnsWidth":[1,1],"axis":"both","cols":2,"layout":[{"i":"shipping_address","x":0,"y":0,"w":1,"h":1},{"i":"shipping_method","x":0,"y":1,"w":1,"h":1},{"i":"payment_method","x":1,"y":0,"w":1,"h":1},{"i":"summary","x":1,"y":1,"w":1,"h":1}]},"3columns":{"frontendColumns":3,"columnsWidth":[1,1,1],"axis":"both","cols":3,"layout":[{"i":"shipping_address","x":0,"y":0,"w":1,"h":1},{"i":"shipping_method","x":1,"y":0,"w":1,"h":1},{"i":"payment_method","x":1,"y":3,"w":1,"h":1},{"i":"summary","x":2,"y":0,"w":1,"h":1}]}},"modern":{"1column":{"frontendColumns":1,"columnsWidth":[1],"axis":"both","cols":1,"layout":[{"i":"shipping_address","x":0,"y":0,"w":1,"h":1},{"i":"shipping_method","x":0,"y":1,"w":1,"h":1},{"i":"payment_method","x":0,"y":3,"w":1,"h":1},{"i":"summary","x":0,"y":4,"w":1,"h":1}]},"2columns":{"frontendColumns":2,"columnsWidth":[2,1],"axis":"y","cols":3,"layout":[{"i":"shipping_address","x":0,"y":0,"w":2,"h":1},{"i":"shipping_method","x":0,"y":1,"w":2,"h":1},{"i":"payment_method","x":0,"y":3,"w":2,"h":1},{"i":"summary","x":2,"y":0,"w":1,"h":1,"static":true,"axis":"x"}]},"3columns":{"frontendColumns":3,"columnsWidth":[1,1,1],"axis":"both","cols":3,"layout":[{"i":"shipping_address","x":0,"y":0,"w":1,"h":1},{"i":"shipping_method","x":1,"y":0,"w":1,"h":1},{"i":"payment_method","x":2,"y":0,"w":1,"h":1},{"i":"summary","x":1,"y":2,"w":1,"h":1}]}}}',
                    ],
                ]
            ],
        ];
    }
}
