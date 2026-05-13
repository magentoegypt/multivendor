<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/28/2016
 * Time: 10:17 AM
 */

namespace Vnecoms\VendorsRMA\Model\System\Config\Source;


class Notify implements \Magento\Framework\Option\ArrayInterface
{
    const ALL = "all";
    const ADMIN = "admin";
    const CUSTOMER = "customer";
    const VENDOR = "vendor";
    const DISABLE = "disable";
    /**
     * Options getterRussian
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::ALL, 'label' => __('All')],
            ['value' => self::ADMIN, 'label' => __('Admin and Vendor')],
            ['value' => self::CUSTOMER, 'label' => __('Customer Only')],
            ['value' => self::DISABLE, 'label' => __('Disable')],
        ];
    }

}