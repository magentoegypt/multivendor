<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Plugin\Framework\View\Page\Config\Renderer;

use Amasty\CheckoutCore\Model\Config as ConfigProvider;
use Magento\Framework\View\Asset\GroupedCollection;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\View\Page\Config\Renderer;

class DisableJsMixins
{
    /**
     * @var Repository
     */
    private $assetRepo;

    /**
     * @var GroupedCollection
     */
    private $pageAssets;

    /**
     * @var ConfigProvider
     */
    private $checkoutConfig;

    public function __construct(
        ConfigProvider $checkoutConfig,
        Repository $assetRepo,
        GroupedCollection $pageAssets
    ) {
        $this->checkoutConfig = $checkoutConfig;
        $this->assetRepo = $assetRepo;
        $this->pageAssets = $pageAssets;
    }

    /**
     * Disable Amasty OSC js mixins if module is disabled
     *
     * @param Renderer $subject
     * @param array $resultGroups
     *
     * @return array
     */
    public function beforeRenderAssets(Renderer $subject, $resultGroups = [])
    {
        if (!$this->checkoutConfig->isEnabled()) {
            $file = 'Amasty_CheckoutCore::js/amastyCheckoutDisabled.js';
            $asset = $this->assetRepo->createAsset($file);
            $this->pageAssets->insert($file, $asset, 'requirejs/require.js');
            return [$resultGroups];
        }

        return [$resultGroups];
    }
}
