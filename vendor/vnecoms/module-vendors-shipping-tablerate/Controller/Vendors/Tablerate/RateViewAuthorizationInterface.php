<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsShippingTableRate\Controller\Vendors\Tablerate;

interface RateViewAuthorizationInterface
{
    /**
     * Check if rate can be viewed by user
     *
     * @param \Vnecoms\VendorsShippingTableRate\Api\Data\TablerateInterface $rate
     * @return bool
     */
    public function canView(\Vnecoms\VendorsShippingTableRate\Api\Data\TablerateInterface $rate);
    
}
