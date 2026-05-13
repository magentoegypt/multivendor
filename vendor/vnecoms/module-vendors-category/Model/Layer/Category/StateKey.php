<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Model\Layer\Category;

use Magento\Catalog\Model\Layer\StateKeyInterface;

class StateKey implements StateKeyInterface
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
    }

    /**
     * Build state key
     *
     * @param \Magento\Catalog\Model\Category $vendor
     * @return string
     */
    public function toString($vendor)
    {
        return '_VENDOR_'.$vendor->getId().'_CAT_';
    }
}
