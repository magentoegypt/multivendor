<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model\Template;

/**
 * Cms Template Filter Model.
 */
class UrlFilter extends AbstractFilter
{
    /**
     * Retrieve vendor url directive.
     *
     * @param array $construction
     *
     * @return string
     */
    public function urlDirective($construction)
    {
        $skipParams = ['id'];
        $this->tokenizeParams->setString($construction[2]);
        $urlParameters = $this->tokenizeParams->tokenize();
        $path = trim($urlParameters['path']);
        if ($vendor = $this->getVendor()) {
            return $this->getVendorHelper()->getUrl($vendor, $path);
        }

        return '';
    }
}
