<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/28/2016
 * Time: 10:17 AM
 */

namespace Vnecoms\RMA\Model\System\Config\Source;

class Notify implements \Magento\Framework\Option\ArrayInterface
{
    const ALL = "all";
    const ADMIN = "admin";
    const CUSTOMER = "customer";
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
            ['value' => self::ADMIN, 'label' => __('Admin Only')],
            ['value' => self::CUSTOMER, 'label' => __('Customer Only')],
            ['value' => self::DISABLE, 'label' => __('Disable')],
        ];
    }
}
