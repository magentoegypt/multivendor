<?php 
namespace MagentoEgypt\VendorExtend\Block\BannerManager;

class Banner extends \Vnecoms\BannerManager\Block\Banner
{
    /**
     * @param \Vnecoms\BannerManager\Model\Banner $banner
     * @return bool
     */
    public function isAvailableBanner($banner)
    {
        if($banner->getToDate() == null) {
			$banner->setToDate('');
		}
		if($banner->getFromDate() == null) {
            $banner->setFromDate('');
        }
        return parent::isAvailableBanner($banner);
    }
}