<?php

namespace Vnecoms\BannerManager\Model\ResourceModel;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;

/**
 * Class Resource Model Banner
 * @category Vnecoms
 * @package  Vnecoms_BannerManager
 * @module   BannerManager
 * @author   Vnecoms Developer Team
 */
class Banner extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * Store model
     *
     * @var null|\Magento\Store\Model\Store
     */
    protected $_store = null;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Banner constructor
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param null $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->_storeManager = $storeManager;
    }

    /**
     * Construct
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('ves_bannermanager_banner', 'banner_id');
    }

    /**
     * Before Delete
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    protected function _beforeDelete(\Magento\Framework\Model\AbstractModel $object)
    {
        $condition = ['banner_id = ?' => (int)$object->getId()];

        $this->getConnection()->delete($this->getTable('ves_bannermanager_banner'), $condition);

        return parent::_beforeDelete($object);
    }

    /**
     * Load Banner Model
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @param mixed $value
     * @param null $field
     * @return $this
     */
    public function load(\Magento\Framework\Model\AbstractModel $object, $value, $field = null)
    {
        if (!is_numeric($value) && is_null($field)) {
            $field = 'identifier';
        }
        return parent::load($object, $value, $field);
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
        if (!$this->IsUniqueBannerToVendor($object)) {
            throw new LocalizedException(
                __('The banner identifier must be changed.It can\'t same with others page identifier\'s.')
            );
        }

        if (!$object->getVendorId()) {
            $object->setVendorId($this->getVendor()->getId());
        }

        return parent::_beforeSave($object);
    }


    /**
     * Set store model
     *
     * @param \Magento\Store\Model\Store $store
     * @return $this
     */
    public function setStore($store)
    {
        $this->_store = $store;
        return $this;
    }

    /**
     * Retrieve store model
     *
     * @return \Magento\Store\Model\Store
     */
    public function getStore()
    {
        return $this->_storeManager->getStore($this->_store);
    }

    /**
     * Check for unique of identifier of page to selected vendor.
     *
     * @param AbstractModel $object
     *
     * @return bool
     */
    public function IsUniqueBannerToVendor(AbstractModel $object)
    {
        $select = $this->getConnection()->select()
            ->from(array('cp' => $this->getMainTable()))
            ->where('cp.identifier = ?', $object->getData('identifier'))
            ->where('cp.vendor_id = ?', $object->getData('vendor_id'))
        ;

        if ($object->getId()) {
            $select->where('cp.banner_id <> ?', $object->getId());
        }

        if ($this->getConnection()->fetchOne($select)) {
            return false;
        }

        return true;
    }

}
