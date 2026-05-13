<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Controller\Vendors\Wysiwyg\Images;

class Thumbnail extends \Vnecoms\VendorsCms\Controller\Vendors\Wysiwyg\Images
{
    /**
     * Generate image thumbnail on the fly.
     *
     * @return \Magento\Framework\Controller\Result\Raw
     */
    public function execute()
    {
        $file = $this->getRequest()->getParam('file');
        $file = $this->_objectManager->get('Vnecoms\VendorsCms\Helper\Wysiwyg\Images')->idDecode($file);
        $thumb = $this->getStorage()->resizeOnTheFly($file);
        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $resultRaw = $this->resultRawFactory->create();
        if ($thumb !== false) {
            /** @var \Magento\Framework\Image\Adapter\AdapterInterface $image */
            $image = $this->_objectManager->get('Magento\Framework\Image\AdapterFactory')->create();
            $image->open($thumb);
            $resultRaw->setHeader('Content-Type', $image->getMimeType());
            $resultRaw->setContents($image->getImage());

            return $resultRaw;
        } else {
            // todo: generate some placeholder
        }
    }
}
