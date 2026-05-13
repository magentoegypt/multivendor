<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Controller\Vendors\Wysiwyg\Images;

use Magento\Framework\App\Filesystem\DirectoryList;

class DeleteFiles extends \Vnecoms\VendorsCms\Controller\Vendors\Wysiwyg\Images
{
    /**
     * Delete file from media storage.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            if (!$this->getRequest()->isPost()) {
                throw new \Exception('Wrong request.');
            }
            $files = $this->getRequest()->getParam('files');

            /** @var $helper \Vnecoms\VendorsCms\Helper\Wysiwyg\Images */
            $helper = $this->_objectManager->get('Vnecoms\VendorsCms\Helper\Wysiwyg\Images');
            $path = $this->getStorage()->getSession()->getCurrentPath();
            foreach ($files as $file) {
                $file = $helper->idDecode($file);
                /** @var \Magento\Framework\Filesystem $filesystem */
                $filesystem = $this->_objectManager->get('Magento\Framework\Filesystem');
                $dir = $filesystem->getDirectoryRead(DirectoryList::MEDIA);
                $filePath = $path.'/'.$file;
                if ($dir->isFile($dir->getRelativePath($filePath))) {
                    $this->getStorage()->deleteFile($filePath);
                }
            }

            return $this->resultRawFactory->create();
        } catch (\Exception $e) {
            $result = ['error' => true, 'message' => $e->getMessage()];
            /** @var \Magento\Framework\Controller\Result\Json $resultJson */
            $resultJson = $this->resultJsonFactory->create();

            return $resultJson->setData($result);
        }
    }
}
