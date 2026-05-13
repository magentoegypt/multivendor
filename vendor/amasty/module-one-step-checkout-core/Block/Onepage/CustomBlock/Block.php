<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Block\Onepage\CustomBlock;

use Magento\Framework\View\Element\Context;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Cms\Model\BlockFactory;
use Amasty\CheckoutCore\Model\Config;

class Block extends \Magento\Cms\Block\Block
{
    /**
     * @var string|int|null
     */
    private $blockId;

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        Context $context,
        FilterProvider $filterProvider,
        StoreManagerInterface $storeManager,
        BlockFactory $blockFactory,
        Config $config,
        array $data = []
    ) {
        parent::__construct($context, $filterProvider, $storeManager, $blockFactory, $data);
        $this->config = $config;
        $this->setData('cache_lifetime', 86400);
    }

    /**
     * @return string|int
     */
    public function getBlockId()
    {
        if ($this->blockId === null) {
            $this->blockId = $this->config->getCustomBlockIdByPosition($this->getPosition() . '_');
        }

        return $this->blockId;
    }

    /**
     * @inheritdoc
     */
    public function getCacheKeyInfo()
    {
        return array_merge(parent::getCacheKeyInfo(), ['store' . $this->_storeManager->getStore()->getId()]);
    }
}
