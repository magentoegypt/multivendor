<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Controller\Vendors\Wysiwyg\Images;

class Index extends \Vnecoms\VendorsCms\Controller\Vendors\Wysiwyg\Images
{
    /**
     * Index action.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $this->_objectManager->get(\Vnecoms\VendorsCms\Helper\Wysiwyg\Images::class)->getCurrentPath();
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }
        $this->_initAction();
        /** @var \Magento\Framework\View\Result\Layout $resultLayout */
        $resultLayout = $this->resultLayoutFactory->create();
        $resultLayout->addHandle('overlay_popup');
        //$block = $resultLayout->getLayout()->getBlock('wysiwyg_images.js');
        return $resultLayout;
    }
}
