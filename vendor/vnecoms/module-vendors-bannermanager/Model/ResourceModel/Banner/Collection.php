<?php

namespace Vnecoms\BannerManager\Model\ResourceModel\Banner;

use Vnecoms\BannerManager\Model\Banner;
use Vnecoms\BannerManager\Model\ResourceModel\Banner as ResourceBanner;
use Vnecoms\BannerManager\Model\ResourceModel\AbstractCollection;
use Magento\Framework\DB\Select;

/**
 * Class Banner Collection
 * @category Vnecoms
 * @package  Vnecoms_BannerManager
 * @module   BannerManager
 * @author   Vnecoms Developer Team
 */
class Collection extends AbstractCollection
{

    /** @var int|null  */
    protected $_storeViewId = null;

    /** @var \Magento\Framework\Model\ResourceModel\Db\AbstractDb */
    protected $_resource;

    /** @var  \Magento\Store\Model\StoreManagerInterface */
    protected $_storeManager;

    /**
     * @var string
     */
    protected $_idFieldName = 'banner_id';

    /** @var array */
    protected $_addedTable = [];

    /** @var \Vnecoms\Vendors\Model\Session  */
    protected $vendorSession;

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $eventPrefix = '';

    /**
     * Parameter name in event
     * In observe method you can use $observer->getEvent()->getStock() in this case
     *
     * @var string
     */
    protected $eventObject = '';


    /**
     * Prepare parent constructor
     *
     * @param void
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Vnecoms\BannerManager\Model\Banner', 'Vnecoms\BannerManager\Model\ResourceModel\Banner');
        $this->_map['fields']['banner_id'] = 'main_table.banner_id';
    }

    /**
     * Retrieve option array
     *
     * @return array
     */
    public function getOptionArray()
    {
        $optionArray = ['' => ' '];
        foreach ($this->toOptionArray() as $option) {
            $optionArray[$option['value']] = $option['label'];
        }
        return $optionArray;
    }

    /**
     * Retrieve option array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $banners = parent::_toOptionArray('banner_id', 'title');
        if (!count($banners)) {
            array_unshift(
                $banners,
                ['value' => 0, 'label' => __('No banners found')]
            );
        }
        return $banners;
    }

    /**
     * Get Store View
     *
     * @return int|null
     */
    public function getStoreViewId()
    {
        return $this->_storeViewId;
    }

    /**
     * Set Store View Id
     *
     * @param $storeViewId
     * @return $this
     */
    public function setStoreViewId($storeViewId)
    {
        $this->_storeViewId = $storeViewId;
        return $this;
    }

    /**
     * Get Banner By ID
     *
     * @param $id
     * @return $this
     */
    public function getById($id)
    {
        $this->addFieldToFilter('banner_id', $id);
        return $this;
    }

    /**
     * Get Connection
     *
     * @return false|\Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function getConnection()
    {
       return $this->getResource()->getConnection();
    }

    /**
     * Before load
     *
     * @return $this
     */
    protected function _beforeLoad()
    {
        return parent::_beforeLoad();
    }

    /**
     * @return $this|void
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        if ($this->vendorSession instanceof \Vnecoms\Vendors\Model\Session) {
            $vendor = $this->vendorSession->getVendor();
        } else {
            $vendor = \Magento\Framework\App\ObjectManager::getInstance()->create('\Vnecoms\Vendors\Model\Session')->getVendor();
        }

        $this->addVendorFilter($vendor);

    }

    /**
     * After load
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        return parent::_afterLoad();

    }

    /**
     * Filter by Add Field
     *
     * @param array|string $field
     * @param null $condition
     * @return $this
     */
    public function addFieldToFilter($field, $condition = null)
    {
        $attributes = array(
            'banner_id',
            'title',
            'status',
            'identifier',
            'effect',
            'maintable',
        );
        $storeViewId = $this->getStoreViewId();
        if (in_array($field, $attributes) && $storeViewId) {
            if (!in_array($field, $this->_addedTable)) {
                $sql = sprintf(
                    'main_table.banner_id = %s.banner_id AND %s.store_id = %s  AND %s.attribute_code = %s ',
                    $this->getConnection()->quoteTableAs($field),
                    $this->getConnection()->quoteTableAs($field),
                    $this->getConnection()->quote($storeViewId),
                    $this->getConnection()->quoteTableAs($field),
                    $this->getConnection()->quote($field)
                );
                $this->getSelect()
                    ->joinLeft(array($field => $this->getTable('ves_bannermanager_banner')), $sql, array());
                $this->_addedTable[] = $field;
            }
            $fieldNullCondition = $this->_translateCondition("$field.value", ['null' => TRUE]);
            $mainfieldCondition = $this->_translateCondition("main_table.$field", $condition);
            $fieldCondition = $this->_translateCondition("$field.value", $condition);
            $condition = $this->_implodeCondition(
                $this->_implodeCondition($fieldNullCondition, $mainfieldCondition, \Zend_Db_Select::SQL_AND),
                $fieldCondition,
                \Zend_Db_Select::SQL_OR
            );
            $this->_select->where($condition, NULL, \Magento\Framework\DB\Select::TYPE_CONDITION);
            return $this;
        }
        if ($field == 'store_id') {
            $field = 'main_table.banner_id';
        }

        if ($field == 'from_date' || $field == 'to_date') {
            // Fix if apply filter on display_from or display_to in grid
            $resultCondition = $this->_translateCondition($field, ['null' => ''])
                . ' OR ' . $this->_translateCondition($field, $condition);
            return $this->getSelect()->where($resultCondition, null, Select::TYPE_CONDITION);
        }

        return parent::addFieldToFilter($field, $condition);
    }
    

    /**
     * Implode Condition
     *
     * @param $firstCondition
     * @param $secondCondition
     * @param $type
     * @return string
     */
    protected function _implodeCondition($firstCondition, $secondCondition, $type)
    {
        return '(' . implode(') ' . $type . ' (', [$firstCondition, $secondCondition]) . ')';
    }

    /**
     * @param \Vnecoms\Vendors\Model\Vendor $vendor
     *
     * @return $this
     */
    public function addVendorFilter($vendor)
    {
        $this->addFieldToFilter('vendor_id', ['eq' => $vendor->getId()]);
        return $this;
    }

    /**
     * Add date filter
     *
     * @param string $currentDate
     * @return $this
     */
    public function addDateFilter($currentDate)
    {
        $this
            ->getSelect()
            ->where(
                '(main_table.from_date IS NULL OR main_table.from_date <= "' . $currentDate . '")
                AND (main_table.to_date IS NULL OR main_table.to_date >= "' . $currentDate . '")'
            );
        return $this;
    }



}
