<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsSearch\Plugin\Search\Adapter\Mysql;

class TemporaryStorage
{
    /**
     * @param \Magento\Catalog\Model\Layer $layer
     * @param $result
     * @return mixed
     */
    public function beforeStoreApiDocuments(
        \Magento\Framework\Search\Adapter\Mysql\TemporaryStorage $storage,
        $documents
    )
    {
        foreach ($documents as $document) {
            if (!$document->getCustomAttribute('score')) {
                $obj = new \Magento\Framework\DataObject();
                $obj->setValue(null);
                $document->setCustomAttribute('score', $obj);
            }
        }

    }
}
