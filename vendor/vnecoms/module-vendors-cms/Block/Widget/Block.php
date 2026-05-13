<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Vnecoms\VendorsCms\Block\Widget;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use VNecoms\VendorsCms\Model\Block as CmsBlock;
use Magento\Widget\Block\BlockInterface;

/**
 * Cms Static Block Widget
 *
 * @author Magento Core Team <core@magentocommerce.com>
 */
class Block extends \Magento\Cms\Block\Widget\Block implements BlockInterface, IdentityInterface
{
    /**
     * Block factory
     *
     * @var \Magento\Cms\Model\BlockFactory
     */
    protected $_vendorBlockFactory;

    /**
     * @var CmsBlock
     */
    private $block;

    /**
     * Block constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Cms\Model\Template\FilterProvider $filterProvider
     * @param \Vnecoms\VendorsCms\Model\BlockFactory $vendorBlockFactory
     * @param \Magento\Cms\Model\BlockFactory $blockFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Cms\Model\Template\FilterProvider $filterProvider,
        \Vnecoms\VendorsCms\Model\BlockFactory $vendorBlockFactory,
        \Magento\Cms\Model\BlockFactory $blockFactory,
        array $data = []
    ) {
        parent::__construct($context, $filterProvider, $blockFactory, $data);
        $this->_filterProvider = $filterProvider;
        $this->_vendorBlockFactory = $vendorBlockFactory;
    }
    /**
     * Prepare block text and determine whether block output enabled or not.
     *
     * Prevent blocks recursion if needed.
     *
     * @return $this
     */
    protected function _beforeToHtml()
    {
        if ($this->getData('type_block') != "vendor_block") return parent::_beforeToHtml();
        \Magento\Framework\View\Element\Template::_beforeToHtml();
        $blockId = $this->getData('block_id');
        $blockHash = get_class($this) . $blockId;

        if (isset(self::$_widgetUsageMap[$blockHash])) {
            return $this;
        }
        self::$_widgetUsageMap[$blockHash] = true;

        $block = $this->getBlock();

        if ($block && $block->isActive()) {
            try {
                $storeId = $this->getData('store_id') ?? $this->_storeManager->getStore()->getId();
                $this->setText(
                    $this->_filterProvider->getBlockFilter()->setStoreId($storeId)->filter($block->getContent())
                );
            } catch (NoSuchEntityException $e) {
            }
        }
        unset(self::$_widgetUsageMap[$blockHash]);
        return $this;
    }

    /**
     * Get block
     *
     * @return CmsBlock|null
     */
    private function getBlock(): ?CmsBlock
    {
        if ($this->block) {
            return $this->block;
        }

        $blockId = $this->getData('block_id');

        if ($blockId) {
            try {
               // $storeId = $this->_storeManager->getStore()->getId();
                /** @var \Magento\Cms\Model\Block $block */
                $block = $this->_vendorBlockFactory->create();
                $block->load($blockId);
                $this->block = $block;

                return $block;
            } catch (NoSuchEntityException $e) {
            }
        }

        return null;
    }
}
