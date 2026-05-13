<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */


namespace Vnecoms\VendorsCms\Block\App;

/**
 * Interface AppInterface
 * @package Vnecoms\VendorsCms\Block\App
 */
interface AppInterface
{
    /**
     * Add data to the widget.
     * Retains previous data in the widget.
     *
     * @param array $arr
     *
     * @return $this
     */
    public function addData(array $arr);

    /**
     * Overwrite data in the widget.
     *
     * Param $key can be string or array.
     * If $key is string, the attribute value will be overwritten by $value.
     * If $key is an array, it will overwrite all the data in the widget.
     *
     * @param string|array $key
     * @param mixed        $value
     *
     * @return \Magento\Framework\DataObject
     */
    public function setData($key, $value = null);
}
