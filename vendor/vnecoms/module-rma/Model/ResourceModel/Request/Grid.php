<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Model\ResourceModel\Request;

/**
 * App page collection
 */
class Grid extends \Vnecoms\RMA\Model\ResourceModel\Request\Collection
{
    /**
     * Init select
     *
     * @return $this
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->addAttributeToSelect("ip_address");
        return $this;
    }
}
