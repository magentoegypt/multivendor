<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\ResourceModel\Field;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \Amasty\CheckoutCore\Model\Field::class,
            \Amasty\CheckoutCore\Model\ResourceModel\Field::class
        );
    }

    /**
     * @param int $storeId
     *
     * @return $this
     */
    public function joinStore($storeId)
    {
        if ($storeId) {
            $select = $this->getSelect();
            $select2 = clone $select;
            $select
                ->where('main_table.store_id=?', $storeId)
                ->orWhere(
                    'main_table.attribute_id NOT IN (?)',
                    $select2->reset('columns')
                        ->columns('attribute_id')
                        ->where('main_table.store_id=?', $storeId)
                );
            $select->where('main_table.store_id=?', 0)
                ->order("sort_order ASC");
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function joinAttribute()
    {
        $this->getSelect()
            ->join(
                ['a' => $this->getTable('eav_attribute')],
                'a.attribute_id = main_table.attribute_id',
                ['attribute_code', 'default_label' => 'frontend_label', 'frontend_input']
            );

        return $this;
    }

    /**
     * @param int $storeId
     *
     * @return Collection
     */
    public function addFilterByStoreId($storeId)
    {
        return $this->addFieldToFilter('store_id', $storeId);
    }

    /**
     * @param int $storeId
     *
     * @return Collection
     */
    public function getAttributeCollectionByStoreId($storeId = \Amasty\CheckoutCore\Model\Field::DEFAULT_STORE_ID)
    {
        if ($storeId != \Amasty\CheckoutCore\Model\Field::DEFAULT_STORE_ID) {
            return $this->joinStore($storeId)->joinAttribute();
        } else {
            return $this->addFilterByStoreId($storeId)->joinAttribute()->setOrder('sort_order', 'ASC');
        }
    }
}
