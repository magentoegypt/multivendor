<?php

namespace Vnecoms\RMA\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\AbstractModel;

class Status extends AbstractDb
{
    /**
     * @var \Magento\Framework\Stdlib\StringUtils
     */
    protected $string;

    /**
     * Reason constructor.
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param null $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Stdlib\StringUtils $string,
        $connectionName = null
    ) {
        $this->string = $string;
        parent::__construct($context, $connectionName);
    }
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('ves_rma_status', 'status_id');
    }
    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        if ($object->hasStoreLabels()) {
            $this->saveStoreLabels($object->getId(), $object->getStoreLabels());
        }
       // var_dump($object->getStoreTemplates());exit;
        if ($object->hasStoreTemplates()) {
            $this->saveTemplates($object->getId(), $object->getStoreTemplates());
        }
        return parent::_afterSave($object);
    }
    /**
     * Save status labels for different store views
     *
     * @param int $statusId
     * @param array $labels
     * @throws \Exception
     * @return $this
     */
    public function saveStoreLabels($statusId, $labels)
    {
        $deleteByStoreIds = [];
        $table = $this->getTable('ves_rma_status_store');
        $connection = $this->getConnection();

        $data = [];
        foreach ($labels as $storeId => $label) {
            if ($this->string->strlen($label)) {
                $data[] = ['status_id' => $statusId, 'store_id' => $storeId, 'title' => $label];
            } else {
                $deleteByStoreIds[] = $storeId;
            }
        }

        $connection->beginTransaction();
        try {
            if (!empty($data)) {
                $connection->insertOnDuplicate($table, $data, ['title']);
            }

            if (!empty($deleteByStoreIds)) {
                $connection->delete($table, ['status_id=?' => $statusId, 'store_id IN (?)' => $deleteByStoreIds]);
            }
        } catch (\Exception $e) {
            $connection->rollback();
            throw $e;
        }
        $connection->commit();

        return $this;
    }
    /**
     * Save status template for different store views
     *
     * @param int $statusId
     * @throws \Exception
     * @return $this
     */
    public function saveTemplates($statusId, $templates)
    {
        $table = $this->getTable('ves_rma_status_template');
        $connection = $this->getConnection();
        foreach ($templates as $storeId => $template) {
            $data = [
                'template_status_id' => $statusId,
                'template_admin_notify' => $template["template_admin_notify"],
                'template_customer_notify' => $template["template_customer_notify"],
                'store_id' => $storeId
            ];

            $connection->beginTransaction();
            try {
                if (!empty($data)) {
                    $connection->insertOnDuplicate($table, $data, ['template_admin_notify','template_customer_notify','store_id']);
                }
            } catch (\Exception $e) {
                $connection->rollback();
                throw $e;
            }
            $connection->commit();
        }

        return $this;
    }


    /**
     * Get all existing status labels
     *
     * @param int $statusId
     * @return array
     */
    public function getStoreLabels($statusId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable('ves_rma_status_store'),
            ['store_id', 'title']
        )->where(
            'status_id = :status_id'
        );
        return $this->getConnection()->fetchPairs($select, [':status_id' => $statusId]);
    }

    /**
     * Get all existing status template
     *
     * @param int $statusId
     * @return array
     */
    public function getStoreTemplates($statusId, $storeId = null)
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable('ves_rma_status_template'),
            ['store_id', 'template_admin_notify', 'template_customer_notify']
        );
        if (!$storeId) {
            $select->where(
                'template_status_id = :status_id'
            );
            $datas = $this->getConnection()->fetchAll($select, [':status_id' => $statusId]);
            $newData =[];
            foreach ($datas as $data) {
                $newData[$data["store_id"]] = [
                    "template_admin_notify" => $data["template_admin_notify"],
                    "template_customer_notify" => $data["template_customer_notify"]
                ];
            }
        } else {
            $select->where(
                'store_id = :store_id'
            );
            $select->where(
                'template_status_id = :status_id'
            );
            $datas = $this->getConnection()->fetchAll($select, [':status_id' => $statusId,'store_id'=>$storeId]);
            $newData =[];
            foreach ($datas as $data) {
                $newData = [
                    "template_admin_notify" => $data["template_admin_notify"],
                    "template_customer_notify" => $data["template_customer_notify"]
                ];
            }
        }

        return $newData;
    }
}
