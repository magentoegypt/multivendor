<?php

namespace Vnecoms\VendorsMedia\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class Data extends AbstractHelper
{
    const MEDIA_FOLDER = 'vnecoms_vendorsmedia';
    /**
     * Get allowed image extensions
     *
     * @return multitype:string
     */
    public function getAllowedExtensions()
    {
        return ['jpg', 'jpeg', 'gif', 'png'];
    }
    
    /**
     * @param \Vnecoms\Vendors\Model\Vendor $vendor
     * @return string
     */
    public function getMediaFolder(\Vnecoms\Vendors\Model\Vendor $vendor){
        return self::MEDIA_FOLDER.'/'.$vendor->getVendorId(); 
    }
}
