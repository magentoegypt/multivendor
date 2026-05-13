<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Helper;

use Magento\Framework\App\Helper\Context;

/**
 * CMS Page Helper.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
class App extends \Magento\Framework\App\Helper\AbstractHelper
{
    public function __construct(Context $context)
    {
        parent::__construct($context);
    }

    public function getVendorLayoutHandles()
    {
    }
}
