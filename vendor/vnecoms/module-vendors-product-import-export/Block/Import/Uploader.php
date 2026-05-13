<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsProductImportExport\Block\Import;

class Uploader extends \Magento\Framework\View\Element\Template
{
    /**
     * Get upload URL
     * @return string
     */
    public function getUploadUrl()
    {
        return $this->getUrl('catalog/import/upload');
    }
}
