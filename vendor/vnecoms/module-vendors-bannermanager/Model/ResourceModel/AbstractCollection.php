<?php
/**
* Copyright 2017 Vnecoms. All rights reserved.
* See LICENSE.txt for license details.
*/

namespace Vnecoms\BannerManager\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\Collection;

/**
 * Class AbstractCollection
 * @package Vnecoms\BannerManager\Model\ResourceModel
 */
abstract class AbstractCollection extends Collection\AbstractCollection
{
    /**
     * {@inheritdoc}
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if ($field == 'banner_id') {
            return $this->addBannerFilter($condition);
        }
        if ($field == 'item_id') {
            return $this->addItemFilter($condition);
        }
        return parent::addFieldToFilter($field, $condition);
    }

    /**
     * Add banner filter
     *
     * @param int|array $banner
     * @return $this
     */
    public function addBannerFilter($banner)
    {
        $this->addFilter('banner_id', ['in' => $banner], 'public');
        return $this;
    }

    /**
     * Add item filter
     *
     * @param int|array $item
     * @return $this
     */
    public function addItemFilter($item)
    {
        $this->addFilter('item_id', ['in' => $item], 'public');
        return $this;
    }
}

