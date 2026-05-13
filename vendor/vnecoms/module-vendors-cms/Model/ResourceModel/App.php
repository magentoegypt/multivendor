<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * CMS App Resource Model.
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */

namespace Vnecoms\VendorsCms\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;

class App extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Cache.
     *
     * @var \Magento\Framework\App\CacheInterface
     */
    protected $_cache;

    /**
     * Define main table.
     */
    protected function _construct()
    {
        $this->_init('vnecoms_vendor_cms_app', 'app_id');
    }

    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\App\CacheInterface $cache,
        $connectionName = null
    ) {
    
        parent::__construct($context, $connectionName);
        $this->_cache = $cache;
    }

    /**
     * Perform actions after object load.
     *
     * @param \Vnecoms\VendorsCms\Model\App $object
     *
     * @return $this
     */
    protected function _afterLoad(AbstractModel $object)
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from(
            $this->getTable('vnecoms_vendor_cms_app_option')
        )->where(
            'app_id = ?',
            (int) $object->getId()
        );
        $result = $connection->fetchAll($select);
        $object->setData('page_groups', $result);

        return parent::_afterLoad($object);
    }

    /**
     * Perform actions after object save.
     *
     * @param \Vnecoms\VendorsCms\Model\App $object
     *
     * @return $this
     */
    protected function _afterSave(AbstractModel $object)
    {
        $optionTable = $this->getTable('vnecoms_vendor_cms_app_option');
        $optionLayoutTable = $this->getTable('vnecoms_vendor_cms_app_option_layout');
        $connection = $this->getConnection();

        $select = $connection->select()->from($optionTable, 'option_id')->where('app_id = ?', (int) $object->getId());
        $optionIds = $connection->fetchCol($select);

        $removePageIds = array_diff($optionIds, $object->getData('page_group_ids'));

        if (is_array($optionIds) && count($optionIds) > 0) {
            $inCond = $connection->prepareSqlCondition('option_id', ['in' => $optionIds]);

            $select = $connection->select()->from($optionLayoutTable, 'layout_update_id')->where($inCond);
            $removeLayoutUpdateIds = $connection->fetchCol($select);

            $connection->delete($optionLayoutTable, $inCond);
            $this->_deleteLayoutUpdates($removeLayoutUpdateIds);
        }

        $this->_deleteAppOptions($removePageIds);

      //  $pageGroups = $this->transformPageGroups($object);

       // echo "<pre>";var_dump($pageGroups);die();
        foreach ($object->getData('page_groups') as $pageGroup) {
            $pageLayoutUpdateIds = $this->_saveLayoutUpdates($object, $pageGroup);
            $data = [
                'page_group' => $pageGroup['page_group'],
                'layout_handle' => $pageGroup['layout_handle'],
                'block_reference' => $pageGroup['block_reference'],
                'page_for' => $pageGroup['page_for'],
                'entities' => $pageGroup['entities'],
               // 'template' => 'app/static_block/default.phtml',
                'template' => isset($pageGroup['template']) ? $pageGroup['template'] : '',
            ];
            $optionId = $pageGroup['option_id'];
            if (in_array($pageGroup['option_id'], $optionIds)) {
                $connection->update($optionTable, $data, ['option_id = ?' => (int) $optionId]);
            } else {
                $connection->insert($optionTable, array_merge(['app_id' => $object->getId()], $data));
                $optionId = $connection->lastInsertId($optionTable);
            }
            foreach ($pageLayoutUpdateIds as $layoutUpdateId) {
                $connection->insert(
                    $optionLayoutTable,
                    ['option_id' => $optionId, 'layout_update_id' => $layoutUpdateId]
                );
            }
        }

      //  $this->_cache->clean([\Vnecoms\VendorsCms\Model\App::CACHE_TAG]);
        return parent::_afterSave($object);
    }

    /**
     * @param $object
     *
     * @return array
     */
    protected function transformPageGroups($object)
    {
        $pageGroups = $object->getData('page_groups');

        foreach ($pageGroups as $pageGroup) {
            if ($pageGroup['page_group'] = 'all_pages') {
                //convert to each page
                foreach ($object->getAllHandlesByDefault() as $handle) {
                    $newPageGroup = [
                        'option_id' => $pageGroup['option_id'],
                        'page_group' => $pageGroup['page_group'],
                        'layout_handle' => $handle,
                        'page_for' => $pageGroup['page_for'],
                        'block_reference' => $pageGroup['block_reference'],
                        'entities' => $pageGroup['entities'],
                        'layout_handle_updates' => $pageGroup['layout_handle_updates'],
                        'template' => $pageGroup['template'],
                    ];

                    $pageGroups[] = $newPageGroup;
                }
            }
        }

        return $pageGroups;
    }

    /**
     * Prepare and save layout updates data.
     *
     * @param \Vnecoms\VendorsCms\Model\App $appInstance
     * @param array                         $pageGroupData
     *
     * @return string[] of inserted layout updates ids
     */
    protected function _saveLayoutUpdates($appInstance, $pageGroupData)
    {
        $connection = $this->getConnection();
        $pageLayoutUpdateIds = [];
        $layoutUpdateTable = $this->getTable('vnecoms_vendor_cms_layout_update');

        foreach ($pageGroupData['layout_handle_updates'] as $handle) {
            $xml = $appInstance->generateLayoutUpdateXml(
                $pageGroupData['block_reference'],
                $pageGroupData['template']
            );
            //default homepage
            if ($handle == 'vendorspage_index_index') {
                foreach (['vendorspage_index_index', 'vendorscms_index_index'] as $h) {
                    $insert = ['handle' => $h, 'xml' => $xml];
                    if (strlen($appInstance->getSortOrder())) {
                        $insert['sort_order'] = $appInstance->getSortOrder();
                    }

                    //set vendor for layout
                    $insert['vendor_id'] = $this->getVendorId();

                    $connection->insert($layoutUpdateTable, $insert);
                    $layoutUpdateId = $connection->lastInsertId($layoutUpdateTable);
                    $pageLayoutUpdateIds[] = $layoutUpdateId;
                }
            } else {
                $insert = ['handle' => $handle, 'xml' => $xml];
                //set vendor for layout
                $insert['vendor_id'] = $this->getVendorId();

                if (strlen($appInstance->getSortOrder())) {
                    $insert['sort_order'] = $appInstance->getSortOrder();
                }

                $connection->insert($layoutUpdateTable, $insert);
                $layoutUpdateId = $connection->lastInsertId($layoutUpdateTable);
                $pageLayoutUpdateIds[] = $layoutUpdateId;
            }
        }

        return $pageLayoutUpdateIds;
    }

    /**
     * @return string|int
     */
    protected function getVendorId()
    {
        return \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Vnecoms\Vendors\Model\Session')->getVendor()->getId();
    }

    /**
     * Perform actions before object delete.
     * Collect page ids and layout update ids and set to object for further delete.
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     *
     * @return $this
     */
    protected function _beforeDelete(AbstractModel $object)
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from(
            ['main_table' => $this->getTable('vnecoms_vendor_cms_app_option')],
            []
        )->joinInner(
            ['layout_page_table' => $this->getTable('vnecoms_vendor_cms_app_option_layout')],
            'layout_page_table.option_id = main_table.option_id',
            ['layout_update_id']
        )->where(
            'main_table.app_id=?',
            $object->getId()
        );
        $result = $connection->fetchCol($select);
        $object->setLayoutUpdateIdsToDelete($result);

        return $this;
    }

    /**
     * Perform actions after object delete.
     * Delete layout updates by layout update ids collected in _beforeSave.
     *
     * @param \Vnecoms\VendorsCms\Model\App $object
     *
     * @return $this
     */
    protected function _afterDelete(AbstractModel $object)
    {
        $this->_deleteLayoutUpdates($object->getLayoutUpdateIdsToDelete());

        return parent::_afterDelete($object);
    }

    /**
     * @param array $optionIds
     *
     * @return $this
     */
    protected function _deleteAppOptions($optionIds)
    {
        $connection = $this->getConnection();
        if ($optionIds) {
            $inCond = $connection->prepareSqlCondition('option_id', ['in' => $optionIds]);
            $connection->delete($this->getTable('vnecoms_vendor_cms_app_option'), $inCond);
        }

        return $this;
    }

    /**
     * Delete layout updates by given ids.
     *
     * @param array $layoutUpdateIds
     *
     * @return $this
     */
    protected function _deleteLayoutUpdates($layoutUpdateIds)
    {
        $connection = $this->getConnection();
        if ($layoutUpdateIds) {
            $inCond = $connection->prepareSqlCondition('layout_update_id', ['in' => $layoutUpdateIds]);
            $connection->delete($this->getTable('vnecoms_vendor_cms_layout_update'), $inCond);
        }

        return $this;
    }
}
