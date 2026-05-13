<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model\Template;

/**
 * Class MediaFilter.
 */
class MediaFilter extends AbstractFilter
{
    /**
     * @param $construction
     * @return string
     */
    public function mediaDirective($construction)
    {
        $this->tokenizeParams->setString($construction[2]);
        $urlParameters = $this->tokenizeParams->tokenize();
        $url = trim($urlParameters['url']);
        return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).$url;
    }
}
