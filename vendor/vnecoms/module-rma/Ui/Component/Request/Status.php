<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Ui\Component\Request;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class Priority
 */
class Status extends Column
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
                    $status = $object_manager->get('\Vnecoms\RMA\Model\Status')->load($item[$this->getData('name')]);
                    $item['code'] = $status->getCode();
                }
            }
        }
        return $dataSource;
    }
}
