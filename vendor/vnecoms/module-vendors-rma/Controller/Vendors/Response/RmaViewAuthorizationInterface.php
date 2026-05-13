<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsRMA\Controller\Vendors\Response;

interface RmaViewAuthorizationInterface
{
    /**
     * Check if rma can be viewed by user
     *
     * @param \Vnecoms\RMA\Model\Request $request
     * @return bool
     */
    public function canView(\Vnecoms\RMA\Model\Reponse $request);
    
}
