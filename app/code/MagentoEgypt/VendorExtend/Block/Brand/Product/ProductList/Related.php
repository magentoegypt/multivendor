<?php 
namespace MagentoEgypt\VendorExtend\Block\Brand\Product\ProductList;

use Vnecoms\VendorsProduct\Model\Source\Approval as ProductApproval;

class Related extends \MGS\Brand\Block\Product\ProductList\Related
{
	protected function _getProductCollection()
	{
		$collection = parent::_getProductCollection();
		$vendor = $this->getVendor();
        if(!empty($vendor)) {
        	$collection->addAttributeToFilter('vendor_id', $vendor->getVendorId())->addAttributeToFilter('approval', ProductApproval::STATUS_APPROVED);
        }
		return $collection;
	}

	public function getVendor()
    {
        return \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Magento\Framework\Registry')->registry('current_vendor_domain');
    }
}