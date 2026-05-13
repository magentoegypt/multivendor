<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Google Address Autocomplete for Magento 2 (System)
 */

namespace Amasty\GoogleAddressAutocomplete\Model\ResourceModel\Region;

class Collection extends \Magento\Directory\Model\ResourceModel\Region\Collection
{
    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _construct()
    {
        $this->_init(\Magento\Directory\Model\Region::class, \Magento\Directory\Model\ResourceModel\Region::class);
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _initSelect()
    {
        $this->getSelect()->from(['main_table' => $this->getMainTable()], ['region_id', 'code', 'country_id']);

        return $this;
    }

    /**
     * @return array
     */
    public function fetchRegions(): array
    {
        $data = $this->getResource()->getConnection()->fetchAssoc($this->getSelect());

        $result = [];

        foreach ($data as $row) {
            $result[$row['country_id']][$row['code']] = $row['region_id'];
        }

        return $result;
    }
}
