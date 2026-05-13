<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model;

use Amasty\CheckoutCore\Api\CheckoutBlocksProviderInterface;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Phrase;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Add checkout blocks config to checkout config
 * @since 3.0.0
 */
class ConfigProvider implements ConfigProviderInterface
{
    public const CONFIG_KEY = 'checkoutBlocksConfig';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CheckoutBlocksProviderInterface
     */
    private $checkoutBlocksProvider;

    public function __construct(
        StoreManagerInterface $storeManager,
        CheckoutBlocksProviderInterface $checkoutBlocksProvider
    ) {
        $this->storeManager = $storeManager;
        $this->checkoutBlocksProvider = $checkoutBlocksProvider;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig(): array
    {
        return [
            static::CONFIG_KEY => $this->getCheckoutBlocksConfig()
        ];
    }

    /**
     * @return array
     */
    public function getCheckoutBlocksConfig(): array
    {
        $blocksConfig = $this->checkoutBlocksProvider->getBlocksConfig($this->getStoreId());
        foreach ($blocksConfig as &$column) {
            foreach ($column as &$block) {
                if (empty($block['title'])) {
                    $block['title'] = $this->getDefaultTitle($block['name']);
                }
            }
        }

        return $blocksConfig;
    }

    /**
     * @param string $blockName
     * @return Phrase|string
     */
    private function getDefaultTitle(string $blockName)
    {
        $defaultTitles = $this->checkoutBlocksProvider->getDefaultBlockTitles();

        return $defaultTitles[$blockName] ?? "";
    }

    /**
     * @return int
     */
    private function getStoreId(): int
    {
        return (int)$this->storeManager->getStore()->getId();
    }
}
