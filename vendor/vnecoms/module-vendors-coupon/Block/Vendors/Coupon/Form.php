<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Vnecoms\VendorsCoupon\Block\Vendors\Coupon;

/**
 * Vendor Notifications block
 */
class Form extends \Vnecoms\Vendors\Block\Vendors\AbstractBlock
{
    /**
     * Escape a string's contents.
     *
     * @param string $string
     * @return string
     */
    protected function _escape($string)
    {
        return htmlspecialchars($string, ENT_COMPAT);
    }
    
    public function getCalendarInit(){
        return $this->_escape(
            json_encode(
                [
                    'calendar' => [
                        'dateFormat' => 'yyyy-MM-dd',
                        'showsTime' => false,
                        'timeFormat' => '',
                        'buttonImage' => $this->getImage(),
                        'buttonText' => 'Select Date',
                        'disabled' => false,
                    ],
                ]
            )
        );
    }
    
    /**
     * Get generate coupon URL
     *
     * @return string
     */
    public function getGenerateUrl(){
        return $this->getUrl('coupon/index/generate');
    }
}
