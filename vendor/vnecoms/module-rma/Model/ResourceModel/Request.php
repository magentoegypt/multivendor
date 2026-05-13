<?php

namespace Vnecoms\RMA\Model\ResourceModel;

class Request extends \Magento\Eav\Model\Entity\AbstractEntity
{

    /**
     * @var \Magento\Framework\Validator\Factory
     */
    protected $_validatorFactory;

    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $dateTime;

    /**
     * @var  \Magento\Framework\App\ResourceConnection
     */
    protected $_coreResource;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param \Magento\Eav\Model\Entity\Context $context
     * @param \Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot $entitySnapshot
     * @param \Magento\Framework\Model\ResourceModel\Db\VersionControl\RelationComposite $entityRelationComposite
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Validator\Factory $validatorFactory
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        \Magento\Eav\Model\Entity\Context $context,
        \Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot $entitySnapshot,
        \Magento\Framework\Model\ResourceModel\Db\VersionControl\RelationComposite $entityRelationComposite,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Validator\Factory $validatorFactory,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        $data = []
    ) {
        parent::__construct($context, $data);
        $this->_scopeConfig = $scopeConfig;
        $this->_validatorFactory = $validatorFactory;
        $this->dateTime = $dateTime;
        $this->storeManager = $storeManager;
        $this->_coreResource = $context->getResource();
        $this->setType('rma');
    }

    /**
     *
     */
    public function getSequenceManager()
    {
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $sequence = $object_manager->get('\Vnecoms\RMA\Model\SequenceManager');
        return $sequence;
    }

    /**
     * Set new increment id to object
     *
     * @param \Magento\Framework\DataObject $object
     * @return $this
     */
    public function setNewIncrementId(\Magento\Framework\DataObject $object)
    {
        if ($object->getIncrementId()) {
            return $this;
        }

        $sequence =  $this->getSequenceManager()->getSequence();
        $incrementId = $sequence->getNextValue();

        if ($incrementId !== false) {
            $object->setIncrementId($incrementId);
        }

        return $this;
    }

    /**
     * @param \Magento\Framework\DataObject $object
     * @return $this
     */
    protected function _beforeSave(\Magento\Framework\DataObject $object)
    {
        if ($object->hasStatus()) {
            $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
            $status = $object_manager->get('\Vnecoms\RMA\Model\Request\Status\State')->load($object->getStatus(), "status_id");
            $object->setData("state", $status->getState());
        }

        return parent::_beforeSave($object);
    }
}
