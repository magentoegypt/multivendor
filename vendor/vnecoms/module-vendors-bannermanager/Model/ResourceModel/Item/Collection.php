<?php

namespace Vnecoms\BannerManager\Model\ResourceModel\Item;

use Vnecoms\BannerManager\Model\ResourceModel\AbstractCollection;
use Vnecoms\BannerManager\Model\Item;
use Vnecoms\BannerManager\Model\ResourceModel\Item as ResourceItem;
use Magento\Store\Model\Store;
use Magento\Framework\DB\Select;

/**
 * Class Collection get all model and return in as array
 * @category Vnecoms
 * @package  Vnecoms\BannerManager\Model\ResourceModel\Item
 * @module   BannerManager
 * @author   Vnecoms Developer Team
 */
class Collection extends AbstractCollection
{
    /**
     *  Prepare Parent Construct
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Vnecoms\BannerManager\Model\Item', 'Vnecoms\BannerManager\Model\ResourceModel\Item');
    }

    /**
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function getConnection()
    {
        return parent::getConnection();
    }

    /**
     * Get item by id
     *
     * @param $itemId
     * @return $this
     */
    public function getById($itemId)
    {
        $this->addFieldToFilter('item_id', $itemId);
        return $this;
    }

    /**
     * Get Field by filter
     *
     * @param array|string $field
     * @param null $condition
     * @return $this
     */
    public function addFieldToFilter($field, $condition = null)
    {
        return parent::addFieldToFilter($field, $condition);
    }

    /**
     * Set Order Item Random by banner id
     *
     * @return $this
     */
    public function setOrderItemsRandomByBannerId()
    {
        $this->getSelect()->orderRand('main_table.banner_id');
        return $this;
    }

}
