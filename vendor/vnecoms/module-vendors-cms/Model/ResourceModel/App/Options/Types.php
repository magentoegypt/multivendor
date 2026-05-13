<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/**
 * Widget Instance Types Options.
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */

namespace Vnecoms\VendorsCms\Model\ResourceModel\App\Options;

class Types implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Vnecoms\VendorsCms\Model\App
     */
    protected $_model;

    /**
     * @param \Vnecoms\VendorsCms\Model\App $app
     */
    public function __construct(\Vnecoms\VendorsCms\Model\App $app)
    {
        $this->_model = $app;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $apps = [];
        $appOptionsArr = $this->_model->getAppsConfigOptionArray('type');
        foreach ($appOptionsArr as $app) {
            $apps[$app['value']] = $app['label'];
        }

        return $apps;
    }
}
