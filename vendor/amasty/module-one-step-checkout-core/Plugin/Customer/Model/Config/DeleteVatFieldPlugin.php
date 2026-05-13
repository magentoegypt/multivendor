<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Plugin\Customer\Model\Config;

use Amasty\CheckoutCore\Model\Field\ConfigManagement\ConfigToField\ProcessDeletedConfigValue;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class DeleteVatFieldPlugin
{
    /**
     * @var ProcessDeletedConfigValue
     */
    private $processDeletedConfigValue;

    public function __construct(ProcessDeletedConfigValue $processDeletedConfigValue)
    {
        $this->processDeletedConfigValue = $processDeletedConfigValue;
    }

    /**
     * @param Value $configValue
     * @return Value
     * @throws AlreadyExistsException
     * @throws NoSuchEntityException
     * @see Value::afterDelete
     */
    public function afterAfterDelete(Value $configValue): Value
    {
        if ($configValue->getPath() !== SaveVatFieldPlugin::CONFIG_PATH) {
            return $configValue;
        }

        $this->processDeletedConfigValue->execute($configValue);

        return $configValue;
    }
}
