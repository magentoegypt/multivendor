<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Field\ConfigManagement\FieldToConfig;

use Amasty\CheckoutCore\Model\Field\ConfigManagement\GetDefaultConfigValue;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class SaveConfigValue
{
    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var ReinitableConfigInterface
     */
    private $reinitableConfig;

    /**
     * @var GetDefaultConfigValue
     */
    private $getDefaultConfigValue;

    public function __construct(
        WriterInterface $configWriter,
        ReinitableConfigInterface $reinitableConfig,
        GetDefaultConfigValue $getDefaultConfigValue
    ) {
        $this->configWriter = $configWriter;
        $this->reinitableConfig = $reinitableConfig;
        $this->getDefaultConfigValue = $getDefaultConfigValue;
    }

    public function execute(string $configPath, string $value): void
    {
        if ($value === $this->getDefaultConfigValue->execute($configPath)) {
            $this->configWriter->delete($configPath);
            $this->reinitableConfig->reinit();
            return;
        }

        $this->configWriter->save($configPath, $value);
        $this->reinitableConfig->reinit();
    }
}
