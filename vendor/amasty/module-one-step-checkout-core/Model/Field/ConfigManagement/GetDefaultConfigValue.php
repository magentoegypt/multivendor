<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Field\ConfigManagement;

use Magento\Config\Model\Config\Structure\SearchInterface;
use Magento\Framework\App\Config\Initial;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Stdlib\ArrayManager;

class GetDefaultConfigValue
{
    /**
     * @var Initial
     */
    private $initialConfig;

    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var SearchInterface
     */
    private $systemConfigSearch;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    public function __construct(
        Initial $initialConfig,
        ArrayManager $arrayManager,
        SearchInterface $systemConfigSearch,
        ObjectManagerInterface $objectManager
    ) {
        $this->initialConfig = $initialConfig;
        $this->arrayManager = $arrayManager;
        $this->systemConfigSearch = $systemConfigSearch;
        $this->objectManager = $objectManager;
    }

    public function execute(string $configPath): ?string
    {
        $configData = $this->initialConfig->getData(ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
        $defaultValue = $this->arrayManager->get($configPath, $configData);

        return $defaultValue !== null ?
            $defaultValue :
            $this->extractValueFromSourceModel($configPath);
    }

    private function extractValueFromSourceModel(string $configPath): ?string
    {
        $configElement = $this->systemConfigSearch->getElement($configPath);

        if ($configElement && isset($configElement->getData()['source_model'])) {
            /** @var OptionSourceInterface $sourceModel */
            $sourceModel = $this->objectManager->get($configElement->getData()['source_model']);

            $options = $sourceModel->toOptionArray();
            $value = reset($options)['value'];
            return $value !== null ? (string) $value : null;
        }

        return null;
    }
}
