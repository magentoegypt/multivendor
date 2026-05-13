<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */


namespace Vnecoms\VendorsRMA\Block\Frontend\Customer\NewRequest;

class Items extends \Vnecoms\RMA\Block\Frontend\Customer\NewRequest\Items
{
    /**
     * get Group Vendor Item
     * @return array
     */
    public function getItemGroupVendor(){
        $_order = $this->getOrder();
        $_items = $_order->getAllItems();
        $groupVendor = [];
        foreach($_items as $item) {
            if($item->getProduct() && $item->getProduct()->getVendorId()) {
                if($item->getVendorId()){
                    $vendorId = $item->getVendorId();
                }else{
                    $vendorId = $item->getProduct()->getVendorId();
                }
                /*Get item by vendor id*/
                if(!isset($quotes[$vendorId])) $quotes[$vendorId] = array();
                $groupVendor[$vendorId][] = $item;
            } else {
                $groupVendor['no_vendor'][] = $item;
            }
        }
        return $groupVendor;
    }

    /**
     * get Vendor Object by Id
     * @param $vendor_id
     * @return mixed
     */

    public function getVendorById($vendor_id){
        $vendor = \Magento\Framework\App\ObjectManager::getInstance()->get(
            'Vnecoms\Vendors\Model\Vendor')->load($vendor_id);
        return $vendor;
    }

}
