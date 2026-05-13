<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model\Template;

class FilterEmulate extends Filter
{
    /**
     * Generate widget with emulation frontend area.
     *
     * @param string[] $value
     *
     * @return string
     */
    public function filter($value)
    {
        return $this->_appState->emulateAreaCode('frontend', [$this, 'filter'], [$value]);
    }
}
