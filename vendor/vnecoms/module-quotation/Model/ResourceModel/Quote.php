<?php
namespace Vnecoms\Quotation\Model\ResourceModel;

use Vnecoms\Quotation\Model\SequenceManager;

class Quote extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var Manager
     */
    protected $sequenceManager;
    
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('vnecoms_quotation_quote', 'entity_id');
    }

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param SequenceManager $sequenceManager
     * @param string $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        SequenceManager $sequenceManager,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->sequenceManager = $sequenceManager;
    }
    /**
     * Perform actions before object save
     * Perform actions before object save, calculate next sequence value for increment Id
     *
     * @param \Magento\Framework\Model\AbstractModel|\Magento\Framework\DataObject $object
     * @return $this
     */
    protected function _beforeSave(\Magento\Framework\Model\AbstractModel $object)
    {
        /** @var \Magento\Sales\Model\AbstractModel $object */
        if ($object->getIncrementId() == null) {
            $object->setIncrementId(
                $this->sequenceManager->getSequence(
                    $object->getStoreId(),
                    $object->getStoreId()
                )->getNextValue()
            );
        }
        return parent::_beforeSave($object);
    }
    
    /**
     * Get Quote By Customer ID(Active)
     * @param \Vnecoms\Quotation\Model\Quote $quote
     * @param $customerId
     * @return $this
     */
    public function loadByCustomerId($quote, $customerId)
    {
        $connection = $this->getConnection();
        $select = $this->_getLoadSelect(
            'customer_id',
            $customerId,
            $quote
        )->where(
            'is_active = ?',
            1
        )->order(
            'updated_at ' . \Magento\Framework\DB\Select::SQL_DESC
        )->limit(
            1
        );

        $data = $connection->fetchRow($select);

        if ($data) {
            $quote->setData($data);
        }

        $this->_afterLoad($quote);

        return $this;
    }
}
