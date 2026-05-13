<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsShippingTableRate\Controller\Vendors\Tablerate;

class RateViewAuthorization implements RateViewAuthorizationInterface
{
    /**
     * @var \Vnecoms\Vendors\Model\Session
     */
    protected $vendorSession;

    /**
     * @param \Vnecoms\Vendors\Model\Session $vendorSession
     */
    public function __construct(
        \Vnecoms\Vendors\Model\Session $vendorSession
    ) {
        $this->vendorSession = $vendorSession;
    }

    /**
     * {@inheritdoc}
     */
    public function canView(\Vnecoms\VendorsShippingTableRate\Api\Data\TablerateInterface $rate)
    {
        $vendor = $this->vendorSession->getVendor();
        if ($rate->getId()
            && $rate->getVendorId()
            && $rate->getVendorId() == $vendor->getId()
        ) {
            return true;
        }
        return false;
    }

}
