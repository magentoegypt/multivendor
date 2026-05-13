<?php

namespace Vnecoms\BannerManager\Model\ResourceModel;

/**
 * Class Resource Model Item
 * @category Vnecoms
 * @package  Vnecoms_BannerManager
 * @module   BannerManager
 * @author   Vnecoms Developer Team
 */
class Item extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * Construct
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('ves_bannermanager_item', 'item_id');
    }

    /**
     * Load Resource Model
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @param mixed $value
     * @param null $field
     * @return $this
     */
    public function load(\Magento\Framework\Model\AbstractModel $object, $value, $field = null)
    {
        return parent::load($object, $value, $field);
    }

}
