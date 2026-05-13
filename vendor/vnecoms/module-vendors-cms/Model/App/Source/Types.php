<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model\App\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class IsActive.
 */
class Types implements OptionSourceInterface
{
    /**
     * @var \Vnecoms\VendorsCms\Model\App
     */
    protected $cmsApp;

    /**
     * Constructor.
     *
     * @param \Vnecoms\VendorsCms\Model\App $cmsApp
     */
    public function __construct(\Vnecoms\VendorsCms\Model\App $cmsApp)
    {
        $this->cmsApp = $cmsApp;
    }

    /**
     * Get options.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return $this->cmsApp->getAppsConfigOptionArray();
    }
}
