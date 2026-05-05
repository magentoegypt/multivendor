<?php declare(strict_types=1);

namespace Mstore\Checkout\Model;

use Magento\Store\Model\App\Emulation;

class CompositeConfigProvider implements ConfigProviderInterface
{
    /**
     * @var ConfigProviderInterface[]
     */
    private $configProviders;

    /**
     * @var Emulation
     */
    private $appEmulation;

    /**
     * @param Emulation $emulation
     * @param ConfigProviderInterface[] $configProviders
     */
    public function __construct(
        Emulation $emulation,
        array $configProviders
    ) {
        $this->appEmulation = $emulation;
        $this->configProviders = $configProviders;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig($cartId = null)
    {
        $config = [];
        foreach ($this->configProviders as $configProvider) {
            $config = array_merge_recursive($config, $configProvider->getConfig($cartId));
        }
        return [$config];
    }
}
