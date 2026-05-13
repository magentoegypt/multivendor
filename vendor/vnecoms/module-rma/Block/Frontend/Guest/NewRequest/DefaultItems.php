<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Block\Frontend\Guest\NewRequest;

class DefaultItems extends \Vnecoms\RMA\Block\Frontend\Customer\NewRequest\DefaultItems
{

    /**
     * get View RMA URL
     */
    public function getViewOtherRmaUrl($requestId)
    {
        return $this->getUrl("vrma/guest/view", ["id"=>$requestId]);
    }
}
