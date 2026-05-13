<?php

namespace Vnecoms\BannerManager\Controller\Vendors;

/**
 * Abstract Class Banner
 * @category Vnecoms
 * @package  Vnecoms_BannerManager
 * @module   BannerManager
 * @author   Vnecoms Developer Team
 */
abstract class Banner extends AbstractAction
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_BannerManager::banners';
    
    protected $vendorSession;
    /**
     * init exist banner
     *
     * @param void
     * @return \Vnecoms\BannerManager\Model\Banner
     */
    public function initBanner()
    {
        $bannerId = (int) $this->getRequest()->getParam('banner_id');
        /** @var \Vnecoms\BannerManager\Model\Banner $banner */
        $banner   = $this->_bannerFactory->create();
        if ($bannerId) {
            $banner->load($bannerId);
        }
        $this->_coreRegistry->register('banner', $banner);
        return $banner;

    }

    /**
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor()
    {
        if ($this->vendorSession == null) {
            $this->vendorSession = \Magento\Framework\App\ObjectManager::getInstance()
                ->get('Vnecoms\Vendors\Model\Session');
        }

        return $this->vendorSession->getVendor();
    }

}
