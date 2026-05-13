<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Controller\Vendors\Wysiwyg\Images;

class TreeJson extends \Vnecoms\VendorsCms\Controller\Vendors\Wysiwyg\Images
{
    /**
     * Tree json action.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        try {
            $this->_initAction();
            /** @var \Magento\Framework\View\Layout $layout */
            $layout = $this->layoutFactory->create();
            $resultJson->setJsonData(
                $layout->createBlock(
                    'Vnecoms\VendorsCms\Block\Vendors\Wysiwyg\Images\Tree'
                )->getTreeJson()
            );
        } catch (\Exception $e) {
            $result = ['error' => true, 'message' => $e->getMessage()];
            $resultJson->setData($result);
        }

        return $resultJson;
    }
}
