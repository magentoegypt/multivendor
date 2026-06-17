<?php 
namespace MagentoEgypt\VendorExtend\Plugin\Block\BannerManager;

class Banner
{
	public function beforeIsAvailableBanner($subject, $banner)
	{
		if($banner->getToDate() == null) {
			$banner->setToDate('');
		}
		if($banner->getFromDate() == null) {
            $banner->setFromDate('');
        }
        return [$banner];
	}
}