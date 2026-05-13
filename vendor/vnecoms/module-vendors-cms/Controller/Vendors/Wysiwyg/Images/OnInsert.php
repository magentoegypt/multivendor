<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Controller\Vendors\Wysiwyg\Images;

class OnInsert extends \Vnecoms\VendorsCms\Controller\Vendors\Wysiwyg\Images
{
    /**
     * Fire when select image.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {

        //$storeId = $this->getRequest()->getParam('store');
        $filename = $this->getRequest()->getParam('filename');

        $filename = $this->helperImages->idDecode($filename);
        $asIs = $this->getRequest()->getParam('as_is');
        $forceStaticPath = $this->getRequest()->getParam('force_static_path');

        $image = $this->helperImages->getImageHtmlDeclaration($filename, $asIs, $forceStaticPath);

        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $resultRaw = $this->resultRawFactory->create();

        return $resultRaw->setContents($image);
    }
}
