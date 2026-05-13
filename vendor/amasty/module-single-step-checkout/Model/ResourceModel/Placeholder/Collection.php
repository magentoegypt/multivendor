<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout for Magento 2
 */

namespace Amasty\Checkout\Model\ResourceModel\Placeholder;

use Amasty\Checkout\Model\Placeholder;
use Amasty\Checkout\Model\ResourceModel\Placeholder as ResourcePlaceholder;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(Placeholder::class, ResourcePlaceholder::class);
    }

    /**
     * @return $this
     */
    protected function _initSelect(): Collection
    {
        parent::_initSelect();
        $this->joinAttributes();

        return $this;
    }

    /**
     * @param int $storeId
     *
     * @return $this
     */
    public function addStoreFilter(int $storeId): Collection
    {
        $connection = $this->getConnection();
        $this->getSelect()
            ->joinLeft(
                ['second_table' => $this->getMainTable()],
                'second_table.attribute_id = main_table.attribute_id AND second_table.store_id = ' . $storeId,
                [
                    'placeholder' => $connection->getIfNullSql(
                        'second_table.placeholder',
                        'main_table.placeholder'
                    )
                ]
            )->where('main_table.store_id = ?', $storeId)
            ->orWhere('main_table.store_id = ?', 0)
            ->group('attribute_id');

        return $this;
    }

    /**
     * @return $this
     */
    private function joinAttributes(): Collection
    {
        $this->getSelect()
            ->join(
                ['a' => $this->getTable('eav_attribute')],
                'a.attribute_id = main_table.attribute_id',
                ['attribute_code']
            );

        return $this;
    }
}
