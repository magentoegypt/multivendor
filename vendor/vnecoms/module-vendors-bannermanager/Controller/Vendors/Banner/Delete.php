<?php

namespace Vnecoms\BannerManager\Controller\Vendors\Banner;

use Vnecoms\BannerManager\Controller\Vendors\Banner;

/**
 * Class Delete Banner
 * @category Vnecoms
 * @package  Vnecoms_BannerManager
 * @module   BannerManager
 * @author   Vnecoms Developer Team
 */
class Delete extends Banner
{
    /**
     * Delete action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\RedirectFactory $resultRedirect */
        $resultRedirect = $this->_resultRedirectFactory->create();
        // check if we know what should be deleted
        $id = $this->getRequest()->getParam('banner_id');

        if ($id) {
            try {
                // init model and delete
                /** @var \Vnecoms\BannerManager\Model\Banner $model */
                $model = $this->_bannerFactory->create();
                /** @var \Vnecoms\BannerManager\Model\Item $itemModel */
                $itemModel  = $this->_itemFactory->create();

                if ($model->load($id)->getVendorId() != $this->getVendor()->getId()) {
                    $this->messageManager->addError(__('You can\'t delete this banner.'));
                    /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                    $resultRedirect = $this->_resultRedirectFactory->create();
                    $resultRedirect->setPath(
                        'bannermanager/*/index', []
                    );

                    return $resultRedirect;
                }

                $model->load($id);
                $model->delete();
                $itemId = $itemModel->getItemByBannerId($id);

                if ($itemId) {
                    $mediaDirectory = $this->_fileSystem
                        ->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
                    foreach ($itemId as $item) {
                        $item = $itemModel->load($item['item_id']);

                        $mediaRootDir = rtrim($mediaDirectory->getAbsolutePath(), '/'); // remove last slash
                        $fileImage      = $mediaRootDir .'/ves/bannermanager/images'. $item['filename'];
                        $directoryImage = pathinfo($fileImage, PATHINFO_DIRNAME).'/';

                        if ( $this->_file->isExists($fileImage) ) {
                            $this->_file->deleteFile($fileImage);

                        }
                        $item->delete();
                    }
                }

                // display success message
                $this->messageManager->addSuccess(__('You deleted the banner.'));
                // go to grid
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                // display error message
                $this->messageManager->addError($e->getMessage());
                // go back to edit form
                return $resultRedirect->setPath('*/*/edit', ['banner_id' => $id]);
            }
        }
        // display error message
        $this->messageManager->addError(__('We can\'t find a block to delete.'));
        // go to grid
        return $resultRedirect->setPath('*/*/');
    }
}
