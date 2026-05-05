<?php 
namespace MagentoEgypt\VendorExtend\Plugin\Model\ResourceModel\Product\Link\Product;

class Collection
{
	public function beforeLoad($collection, $printQuery = false, $logQuery = false)
	{
		if (!$collection->isLoaded()) {
			$vendor = $this->getVendor();
	        if(!empty($vendor)) {
	        	$collection->addAttributeToFilter('vendor_id', $vendor->getVendorId());
	        }
        }
		return [$printQuery, $logQuery];
	}

	public function getVendor()
    {
        return \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Magento\Framework\Registry')->registry('current_vendor_domain');
    }
}