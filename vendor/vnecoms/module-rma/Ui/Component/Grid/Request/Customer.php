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
class Customer extends Column
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
                    $request = \Magento\Framework\App\ObjectManager::getInstance()->get(
                        'Vnecoms\RMA\Model\Request'
                    )->load($item['entity_id']);
                    $item[$this->getData('name')] = $request->getCustomerName()."<br />".$request->getCustomerEmail();
                    //$item["is_admin_read"] = $request->getIsAdminRead();
                }
            }
        }
        return $dataSource;
    }
}
