<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Vnecoms\VendorsCms\Model\ResourceModel\Layout;

/**
 * Layout update resource model.
 */
class Update extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var \Magento\Framework\Cache\FrontendInterface
     */
    private $_cache;

    /**
     * @var array
     */
    private $layoutUpdateCache;

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Framework\Cache\FrontendInterface        $cache
     * @param string                                            $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Cache\FrontendInterface $cache,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->_cache = $cache;
    }

    /**
     * Define main table.
     */
    protected function _construct()
    {
        $this->_init('vnecoms_vendor_cms_layout_update', 'layout_update_id');
    }

    /**
     * Retrieve layout updates by handle.
     *
     * @param string                                        $handle
     * @param \Magento\Framework\View\Design\ThemeInterface $theme
     * @param \Magento\Framework\App\ScopeInterface         $store
     *
     * @return string
     */
    public function fetchUpdatesByHandle(
        $handle,
        $vendorId,
        \Magento\Framework\View\Design\ThemeInterface $theme,
        \Magento\Framework\App\ScopeInterface $store
    ) {
        $bind = ['vendor_id' => $vendorId, 'theme_id' => $theme->getId(), 'store_id' => $store->getId()];
        $cacheKey = implode('-', $bind);
        if (!isset($this->layoutUpdateCache[$cacheKey])) {
            $this->layoutUpdateCache[$cacheKey] = [];
            foreach ($this->getConnection()->fetchAll($this->_getFetchUpdatesByHandleSelect($vendorId)) as $layout) {
                if (!isset($this->layoutUpdateCache[$cacheKey][$layout['handle']])) {
                    $this->layoutUpdateCache[$cacheKey][$layout['handle']] = '';
                }
                $this->layoutUpdateCache[$cacheKey][$layout['handle']] .= $layout['xml'];
            }
        }

        return isset($this->layoutUpdateCache[$cacheKey][$handle]) ? $this->layoutUpdateCache[$cacheKey][$handle] : '';
    }

    /**
     * Get select to fetch updates by handle.
     *
     * @param int  $vendorId
     * @param bool $loadAllUpdates
     *
     * @return \Magento\Framework\DB\Select
     */
    protected function _getFetchUpdatesByHandleSelect($vendorId, $loadAllUpdates = false)
    {
        $select = $this->getConnection()->select()->from(
            ['vnecoms_vendor_cms_layout_update' => $this->getMainTable()],
            ['xml', 'handle']
        )
        ->where('vnecoms_vendor_cms_layout_update.vendor_id = ?', $vendorId)
        ->order(
            'vnecoms_vendor_cms_layout_update.sort_order '.\Magento\Framework\DB\Select::SQL_ASC
        );

        return $select;
    }

    /**
     * Update a "layout update link" if relevant data is provided.
     *
     * @param \Magento\Widget\Model\Layout\Update|\Magento\Framework\Model\AbstractModel $object
     *
     * @return $this
     */
    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        $data = $object->getData();

        $this->_cache->clean();

        return parent::_afterSave($object);
    }
}
