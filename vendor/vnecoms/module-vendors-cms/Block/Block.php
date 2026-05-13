<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Vnecoms\VendorsCms\Block;

/**
 * Cms block content block.
 */
class Block extends \Magento\Framework\View\Element\AbstractBlock implements \Magento\Framework\DataObject\IdentityInterface
{
    /**
     * @var \Vnecoms\VendorsCms\Model\Template\Filter
     */
    protected $_cmsFilter;

    /** @var  \Magento\Framework\Registry */
    protected $_coreRegistry;

    /**
     * Store manager.
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Block factory.
     *
     * @var \Vnecoms\VendorsCms\Model\BlockFactory
     */
    protected $_blockFactory;

    /**
     * Block constructor.
     *
     * @param \Magento\Framework\View\Element\Context    $context
     * @param \Vnecoms\VendorsCms\Model\Template\Filter  $cmsFilter
     * @param \Magento\Framework\Registry                $coreRegistry
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Vnecoms\VendorsCms\Model\BlockFactory     $blockFactory
     * @param array                                      $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Vnecoms\VendorsCms\Model\Template\Filter $cmsFilter,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Vnecoms\VendorsCms\Model\BlockFactory $blockFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_cmsFilter = $cmsFilter;
        $this->_coreRegistry = $coreRegistry;
        $this->_storeManager = $storeManager;
        $this->_blockFactory = $blockFactory;
    }

    /**
     * Prepare Content HTML.
     *
     * @return string
     */
    protected function _toHtml()
    {
        $blockId = $this->getBlockId();
        $vendorId = $this->getVendorId();
        $html = '';
        if ($blockId && $vendorId) {
            /** @var \Vnecoms\VendorsCms\Model\Block $block */
            $block = $this->_blockFactory->create();
            $block->setVendorId($vendorId)->load($blockId);
            if ($block->isActive()) {
                $html = $this->_cmsFilter->filter($block->getContent());
            }
        }

        return $html;
    }

    /**
     * Return identifiers for produced content.
     *
     * @return array
     */
    public function getIdentities()
    {
        return [\Vnecoms\VendorsCms\Model\Block::CACHE_TAG.'_'.$this->getBlockId()];
    }

    /**
     * @return mixed
     */
    public function getVendorId()
    {
        if (!$this->_coreRegistry->registry('vendor')) {
            return false;
        }
        return $this->_coreRegistry->registry('vendor')->getId();
    }
}
