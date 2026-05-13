<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Block\Frontend\Guest\NewRequest;

class BundleItems extends \Vnecoms\RMA\Block\Frontend\Customer\NewRequest\BundleItems
{

    /**
     * get View RMA URL
     */
    public function getViewOtherRmaUrl($requestId)
    {
        return $this->getUrl("vrma/guest/view", ["id"=>$requestId]);
    }
}
