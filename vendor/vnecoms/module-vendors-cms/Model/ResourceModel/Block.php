<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model\ResourceModel;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DB\Select;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\EntityManager;
use Vnecoms\VendorsCms\Api\Data\BlockInterface;

/**
 * CMS block model.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Block extends AbstractDb
{
    /** @var \Magento\Framework\Event\ManagerInterface  */
    protected $_eventManager;

    /** @var Block\CollectionFactory  */
    protected $_blockCollectionFactory;

    /** @var  \Vnecoms\Vendors\Model\Session */
    protected $vendorSession;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var MetadataPool
     */
    protected $metadataPool;

    /**
     * Block constructor.
     *
     * @param Context                                   $context
     * @param Block\CollectionFactory                   $blockCollectionFactory
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param EntityManager                             $entityManager
     * @param MetadataPool                              $metadataPool
     * @param null                                      $connectionName
     */
    public function __construct(
        Context $context,
        \Vnecoms\VendorsCms\Model\ResourceModel\Block\CollectionFactory $blockCollectionFactory,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        EntityManager $entityManager,
        MetadataPool $metadataPool,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->_eventManager = $eventManager;
        $this->_blockCollectionFactory = $blockCollectionFactory;
        $this->entityManager = $entityManager;
        $this->metadataPool = $metadataPool;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection()
    {
        return $this->metadataPool->getMetadata(BlockInterface::class)->getEntityConnection();
    }

    /**
     * Initialize resource model.
     */
    protected function _construct()
    {
        $this->_init('vnecoms_vendor_cms_block', 'block_id');
    }

    /**
     * Perform operations before object save.
     *
     * @param AbstractModel $object
     *
     * @return $this
     *
     * @throws LocalizedException
     */
    protected function _beforeSave(AbstractModel $object)
    {

        /*
         * check unique block identity from a vendor
         */
        if (!$this->IsUniqueBlockToVendor($object)) {
            throw new LocalizedException(
                __('The block identifier must be changed.It can\'t same with others page identifier\'s.')
            );
        }

        if (!$object->getVendorId()) {
            $object->setVendorId($this->getVendor()->getId());
        }

        return parent::_beforeSave($object);
    }

    /**
     * Check for unique of identifier of page to selected vendor.
     *
     * @param AbstractModel $object
     *
     * @return bool
     */
    public function IsUniqueBlockToVendor(AbstractModel $object)
    {
        $select = $this->getConnection()->select()
            ->from(['cp' => $this->getMainTable()])
            ->where('cp.identifier = ?', $object->getData('identifier'))
            ->where('cp.vendor_id = ?', $object->getData('vendor_id'));

        if ($object->getId()) {
            $select->where('cp.block_id <> ?', $object->getId());
        }

        if ($this->getConnection()->fetchOne($select)) {
            return false;
        }

        return true;
    }

    /**
     * @param AbstractModel $object
     * @param mixed         $value
     * @param null          $field
     *
     * @return bool|int|string
     *
     * @throws LocalizedException
     * @throws \Exception
     */
    private function getBlockId(AbstractModel $object, $value, $field = null)
    {
        $entityMetadata = $this->metadataPool->getMetadata(BlockInterface::class);
        if (!is_numeric($value) && $field === null) {
            $field = 'identifier';
        } elseif (!$field) {
            $field = $entityMetadata->getIdentifierField();
        }
        $entityId = $value;
        if ($field != $entityMetadata->getIdentifierField()) {
            $select = $this->_getLoadSelect($field, $value, $object);
            $select->reset(Select::COLUMNS)
                ->columns($this->getMainTable().'.'.$entityMetadata->getIdentifierField())
                ->where('vendor_id = ?', $object->getVendorId())
                ->limit(1);
            $result = $this->getConnection()->fetchCol($select);
            $entityId = count($result) ? $result[0] : false;
        }

        return $entityId;
    }

    /**
     * Load an object.
     *
     * @param \Vnecoms\VendorsCms\Model\Block|AbstractModel $object
     * @param mixed                                         $value
     * @param string                                        $field  field to load by (defaults to model id)
     *
     * @return $this
     */
    public function load(AbstractModel $object, $value, $field = null)
    {
        $blockId = $this->getBlockId($object, $value, $field);
        if ($blockId) {
            $this->entityManager->load($object, $blockId);
        }

        return $this;
    }

    /**
     * Retrieve select object for load object data.
     *
     * @param string                                        $field
     * @param mixed                                         $value
     * @param \Vnecoms\VendorsCms\Model\Block|AbstractModel $object
     *
     * @return Select
     */
    protected function _getLoadSelect($field, $value, $object)
    {
        $entityMetadata = $this->metadataPool->getMetadata(BlockInterface::class);
        $linkField = $entityMetadata->getLinkField();

        $select = parent::_getLoadSelect($field, $value, $object);

        return $select;
    }
}
