<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsShippingTableRate\Ui\Component\Grid;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class Region
 */
class Region extends Column
{


    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item[$this->getData('name')])) {
                    if($item['dest_region_id'] != "*"){
                        $region = \Magento\Framework\App\ObjectManager::getInstance()->get(
                            'Magento\Directory\Model\Region')->load($item['dest_region_id'],"code");
                        $item[$this->getData('name')] = $region->getId() ? $region->getName() : "*";
                    }
                }
            }
        }
        return $dataSource;
    }

}
