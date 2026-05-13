<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Vnecoms\VendorsRMA\Block;

/**
 * Cms block content block.
 */
class Block extends \Magento\Framework\View\Element\AbstractBlock implements \Magento\Framework\DataObject\IdentityInterface
{

    /** @var  \Magento\Framework\Registry */
    protected $_coreRegistry;

    /**
     * Store manager.
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;


    /**
     * Block constructor.
     *
     * @param \Magento\Framework\View\Element\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_coreRegistry = $coreRegistry;
        $this->_storeManager = $storeManager;
    }

    /**
     * Prepare Content HTML.
     *
     * @return string
     */
    protected function _toHtml()
    {
        $blockId = $this->getBlockId();
        $html = '';
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $module = $object_manager->get('Magento\Framework\Module\Manager');
        if ($blockId) {
            $vendorId = $this->getVendorId();
            if ($module->isEnabled("Vnecoms_VendorsCms") && $vendorId != 0) {

                /** @var \Vnecoms\VendorsCms\Model\Block $block */
                $block = $object_manager->get('Vnecoms\VendorsCms\Model\BlockFactory')->create();
                $vendorCmsFilter = $object_manager->get('Vnecoms\VendorsCms\Model\Template\Filter');
                $block->setVendorId($vendorId)->load($blockId);
                if ($block->isActive()) {
                    $html = $vendorCmsFilter->filter($block->getContent());
                }

            }else{
                $storeId = $this->_storeManager->getStore()->getId();
                /** @var \Magento\Cms\Model\Block $block */
                $block = $object_manager->get('Magento\Cms\Model\BlockFactory')->create();
                $cmsFilter = $object_manager->get('Magento\Cms\Model\Template\FilterProvider');
                $block->setStoreId($storeId)->load($blockId);
                if ($block->isActive()) {
                    $html = $cmsFilter->getBlockFilter()->setStoreId($storeId)->filter($block->getContent());
                }
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
        return [\Magento\Cms\Model\Block::CACHE_TAG . '_' . $this->getBlockId()];
    }


}
