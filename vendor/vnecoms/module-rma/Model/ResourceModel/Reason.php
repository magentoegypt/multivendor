<?php

namespace Vnecoms\RMA\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\AbstractModel;

class Reason extends AbstractDb
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
        $this->_init('ves_rma_reason', 'reason_id');
    }
    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    protected function _afterSave(AbstractModel $object)
    {
        if ($object->hasStoreLabels()) {
            $this->saveStoreLabels($object->getId(), $object->getStoreLabels());
        }

        return parent::_afterSave($object);
    }
    /**
     * Save reason labels for different store views
     *
     * @param int $reasonId
     * @param array $labels
     * @throws \Exception
     * @return $this
     */
    public function saveStoreLabels($reasonId, $labels)
    {
        $deleteByStoreIds = [];
        $table = $this->getTable('ves_rma_reason_store');
        $connection = $this->getConnection();

        $data = [];
        foreach ($labels as $storeId => $label) {
            if ($this->string->strlen($label)) {
                $data[] = ['reason_id' => $reasonId, 'store_id' => $storeId, 'title' => $label];
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
                $connection->delete($table, ['reason_id=?' => $reasonId, 'store_id IN (?)' => $deleteByStoreIds]);
            }
        } catch (\Exception $e) {
            $connection->rollback();
            throw $e;
        }
        $connection->commit();

        return $this;
    }
    /**
     * Get all existing reason labels
     *
     * @param int $reasonId
     * @return array
     */
    public function getStoreLabels($reasonId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable('ves_rma_reason_store'),
            ['store_id', 'title']
        )->where(
            'reason_id = :reason_id'
        );
        return $this->getConnection()->fetchPairs($select, [':reason_id' => $reasonId]);
    }
}
