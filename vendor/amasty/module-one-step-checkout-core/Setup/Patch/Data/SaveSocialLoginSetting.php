<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Setup\Patch\Data;

use Amasty\CheckoutCore\Model\Config;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class SaveSocialLoginSetting implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }
    
    /**
     * @return void
     */
    public function apply(): void
    {
        $connection = $this->moduleDataSetup->getConnection();
        $configTable = $this->moduleDataSetup->getTable('core_config_data');
        $select = $connection->select()
            ->from($configTable)
            ->where('path LIKE "' . Config::SOCIAL_LOGIN_POSITION_PATH . '%"');

        $loginConfigRows = $connection->fetchAll($select);

        foreach ($loginConfigRows as $configRow) {
            $positionValue = explode(',', $configRow['value']);
            $configValue = in_array(Config::SOCIAL_LOGIN_CHECKOUT_PAGE_POSITION, $positionValue)
                ? Config::VALUE_SOCIAL_LOGIN_ENABLED
                : Config::VALUE_SOCIAL_LOGIN_DISABLED;

            try {
                $connection->insert(
                    $configTable,
                    [
                        'scope' => $configRow['scope'],
                        'scope_id' => $configRow['scope_id'],
                        'path' => Config::PATH_PREFIX . Config::ADDITIONAL_OPTIONS . Config::FIELD_SOCIAL_LOGIN,
                        'value' => $configValue,
                    ]
                );

            } catch (\Exception $exception) {
                unset($exception);
                continue;
            }
        }
    }
    
    /**
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    public function getAliases(): array
    {
        return [];
    }
}
