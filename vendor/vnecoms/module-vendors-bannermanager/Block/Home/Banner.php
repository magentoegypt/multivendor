<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 * See LICENSE.txt for license details.
 */


namespace Vnecoms\BannerManager\Block\Home;

/**
 * Class with class map capability
 *
 * ...
 */
class Banner extends \Vnecoms\BannerManager\Block\Banner
{
    /**
     * Prepare global layout
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        // Remove default home banner block if use slider
        if ($this->_bannerHelper->getBannerTypeConfig($this->getVendorId()) == 1) {
            $this->getLayout()->getBlock('vendor.home.banner')->setTemplate('');
        }
        return parent::_prepareLayout();
    }

    /**
     * @return \Vnecoms\BannerManager\Model\Banner|bool
     */
    public function getBanner()
    {
        if ($this->_bannerHelper->getBannerTypeConfig($this->getVendorId()) == 0) {
            return false;
        }
        $vendorId = $this->getVendor()->getId();
        $this->setData('banner_id', $this->_bannerHelper->getBannerIdFromConfig($vendorId));
        return parent::getBanner();
    }
}
