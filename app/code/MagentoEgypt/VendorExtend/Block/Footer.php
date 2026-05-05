<?php 
namespace MagentoEgypt\VendorExtend\Block;

class Footer extends \Vnecoms\Vendors\Block\Profile
{
	public function getFooter1()
	{
		if(!$this->hasData('footer1')) {
			$footer1 = $this->_configHelper->getVendorConfig(
	            'page/footer/footer1',
	            $this->getVendor()->getId()
	        );
	        $this->setData('footer1', $footer1);
		}
	    return $this->getData('footer1');
	}

	public function getFooter2()
	{
		if(!$this->hasData('footer2')) {
			$footer2 = $this->_configHelper->getVendorConfig(
	            'page/footer/footer2',
	            $this->getVendor()->getId()
	        );
	        $this->setData('footer2', $footer2);
		}
	    return $this->getData('footer2');
	}

	public function getFooter3()
	{
		if(!$this->hasData('footer3')) {
			$footer3 = $this->_configHelper->getVendorConfig(
	            'page/footer/footer3',
	            $this->getVendor()->getId()
	        );
	        $this->setData('footer3', $footer3);
		}
	    return $this->getData('footer3');
	}

	public function getFooter4()
	{
		if(!$this->hasData('footer4')) {
			$footer4 = $this->_configHelper->getVendorConfig(
	            'page/footer/footer4',
	            $this->getVendor()->getId()
	        );
	        $this->setData('footer4', $footer4);
		}
	    return $this->getData('footer4');
	}

	public function getCopyRight()
	{
		if(!$this->hasData('footer_copy')) {
			$footer_copy = $this->_configHelper->getVendorConfig(
	            'page/footer/footer_copy',
	            $this->getVendor()->getId()
	        );
	        $this->setData('footer_copy', $footer_copy);
		}
	    return $this->getData('footer_copy');
	}

	public function getFooterSocial()
	{
		if(!$this->hasData('footer_social')) {
			$footer_social = $this->_configHelper->getVendorConfig(
	            'page/footer/footer_social',
	            $this->getVendor()->getId()
	        );
	        $this->setData('footer_social', $footer_social);
		}
	    return $this->getData('footer_social');
	}

	public function getFooterLinks()
	{
		if(!$this->hasData('footer_links')) {
			$footer_links = $this->_configHelper->getVendorConfig(
	            'page/footer/footer_links',
	            $this->getVendor()->getId()
	        );
	        $this->setData('footer_links', $footer_links);
		}
	    return $this->getData('footer_links');
	}

}