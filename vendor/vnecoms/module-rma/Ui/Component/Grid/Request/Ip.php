<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Ui\Component\Grid\Request;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class Priority
 */
class Ip extends Column
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
                    $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
                    $geo = $object_manager->get('\Vnecoms\RMA\Helper\Geoip');
                    $geo->setRecord($item[$this->getData('name')]);
                    if ($geo->getRecord()) {
                        $item['geo_country'] = $geo->getCountryName();
                        $item['geo_fag'] = $geo->getFlags();
                    } else {
                        $item['geo_country'] = null;
                        $item['geo_fag'] = null;
                    }
                }
            }
        }

        return $dataSource;
    }
}
