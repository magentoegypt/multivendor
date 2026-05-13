<?php

namespace Vnecoms\BannerManager\Controller\Vendors\Banner;

use Vnecoms\BannerManager\Controller\Vendors\Banner;
use Magento\Framework\Exception\LocalizedException;


/**
 * Class Save Banner
 * @category Vnecoms
 * @package  Vnecoms_BannerManager
 * @module   BannerManager
 * @author   Vnecoms Developer Team
 */
class Save extends Banner
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_BannerManager::bannermanager';
    
    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->_resultRedirectFactory->create();

        //$storeViewId = $this->getRequest()->getParam('store');

        $data = $this->getRequest()->getPostValue();


        if ($data) {

            /** @var \Vnecoms\BannerManager\Model\Banner $banner */
            if ($this->getRequest()->getParam('banner_id')) {
                $banner = $this->initBanner();
            } else {
                $banner = $this->_bannerFactory->create();
            }

            // array store all items
            $itemsStorage = array();

            if(isset($data['banner']['media_gallery']['images'])) {
                $itemsCollection = $data['banner']['media_gallery']['images'];
                //var_dump($itemsCollection);die();
                foreach ($itemsCollection as $id => $images) {

                    $result = array_merge($images, array('item_id_gen' => $id));
                    $itemsStorage[] = $result;

                }
            }
            // set data to banner model
            $banner->setData($data);

            if ($data['speed'] == null) {
                $banner->setData('speed', 300);
            }

            if ($data['delay'] == null) {
                $banner->setData('delay', null);
            }
            if (!empty($this->getVendor()->getId()) && $banner->getVendorId() == null) {
                $vendorId = $this->getVendor()->getId();
                $banner->setVendorId($vendorId);
            }

            $this->_eventManager->dispatch(
                'ves_bannermanager_new_prepare_save',
                [
                    'banner' => $banner,
                    'request' => $this->getRequest()
                ]
            );

            // Save
            try {
                // save the banner data first
                $banner->save();
                $bannerId = $banner->getId();


                // Save items data
                if ($itemsStorage) {

                    //echo "<pre>";var_dump($itemsStorage);die();
                    foreach ($itemsStorage as $items) {

                        // Check If item exist in database or not
                        if ( $items['item_id'] ) {

                            /** @var \Vnecoms\BannerManager\Model\Item $item */
                            $item         = $this->_itemFactory->create();
                            // Load Item by id
                            $itemId       = $item->load($items['item_id']);
                            $itemPosition = $itemId->getItemPosition();
                            $itemStatus = $itemId->getStatus();
                            $itemShortDescription = $itemId->getItemShortDescription();
                            $itemUrl  = $itemId->getUrl();
                            $itemTitle  = $itemId->getTitle();

                            if ( $items['title'] != $itemTitle ) {
                                $itemPosition = $this->_itemFactory->create();
                                $itemPosition->load($items['item_id']);
                                $itemPosition->setData('title', $items['title']);
                                $itemPosition->save();
                            }


                            // if position has changed. Update it
                            if ( $items['position'] != $itemPosition ) {
                                $itemPosition = $this->_itemFactory->create();
                                $itemPosition->load($items['item_id']);
                                $itemPosition->setData('sort_order', $items['position']);
                                $itemPosition->save();
                            }

                            if ( $items['item_status'] != $itemStatus ) {
                                $itemPosition = $this->_itemFactory->create();
                                $itemPosition->load($items['item_id']);
                                $itemPosition->setData('status', $items['item_status']);
                                $itemPosition->save();
                            }

                            if ( $items['item_short_description'] != $itemShortDescription ) {
                                $itemPosition = $this->_itemFactory->create();
                                $itemPosition->load($items['item_id']);
                                $itemPosition->setData('short_description', $items['item_short_description']);
                                $itemPosition->save();
                            }

                            if ( $items['item_url'] != $itemUrl ) {
                                $itemPosition = $this->_itemFactory->create();
                                $itemPosition->load($items['item_id']);
                                $itemPosition->setData('url', $items['item_url']);
                                $itemPosition->save();
                            }

                            $galleryItem['status']              = $items['item_status'];

                            // check item removed or not
                            if ( !empty($items['removed']) || $items['removed'] == 1 ) {
                                if ( $itemId ) $itemId->delete();

                                $mediaDirectory = $this->_fileSystem
                                    ->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
                                $mediaRootDir = rtrim($mediaDirectory->getAbsolutePath(), '/'); // remove last slash
                                $fileImage      = $mediaRootDir .'/ves/bannermanager/images'. $items['file'];
                                $directoryImage = pathinfo($fileImage, PATHINFO_DIRNAME).'/';

                                if ( $this->_file->isExists($fileImage) ) {
                                    $this->_file->deleteFile($fileImage);
                                    //$directory = $this->_file->getParentDirectory($directoryImage);
                                    //$this->_file->deleteDirectory($directory);

                                }
                            }

                        } else {
                            // if item was new then save it
                            if ( empty($items['removed']) || $items['removed'] == 0) {

                                /** @var \Vnecoms\BannerManager\Model\Item $itemGallery */
                                $itemGallery                        = $this->_itemFactory->create();
                                $galleryItem['title']               = $items['title'];
                                $galleryItem['filename']            = $items['file'];
                                $galleryItem['alt']                 = $items['title'];
                                $galleryItem['sort_order']          = $items['position'];
                                $galleryItem['url']                 = $items['item_url'];
                                $galleryItem['short_description']   = $items['item_short_description'];
                                $galleryItem['status']              = $items['item_status'];
/*                                $galleryItem['width']               = $items['item_width'];
                                $galleryItem['height']              = $items['item_height'];*/
                                /*$galleryItem['from_date'] = $this->_localeDate->date($items['from_date'])
                                    ->setTimezone(new \DateTimeZone('UTC'))->format('m/d/Y g:i a');
                                $galleryItem['to_date'] = $this->_localeDate->date($items['to_date'])
                                    ->setTimezone(new \DateTimeZone('UTC'))->format('m/d/Y g:i a');*/
                                $itemGallery->setData($galleryItem);
                                $itemGallery->setData('banner_id', $bannerId);
                                $itemGallery->save();

                            }
                        }

                    }

                }

                // display success message
                $this->messageManager->addSuccess(__('Banner has been saved.'));
                // clear previously saved data from session
                $this->_getSession()->setFormData(false);

                // check if 'Save and Continue'
                if ($this->getRequest()->getParam('back') === 'edit') {
                    return $resultRedirect->setPath(
                        '*/*/edit',
                        [
                            'banner_id' => $banner->getId(),
                            '_current' => true,
                            'store' => null
                        ]
                    );
                } elseif ($this->getRequest()->getParam('back') === 'new') {
                    return $resultRedirect->setPath(
                        '*/*/new',
                        ['_current' => true]
                    );
                }

                // go to grid
                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                // display error message
                $this->messageManager->addError($e, __('Something went wrong while saving the banner.'));
                // save data in session
                $this->_getSession()->setFormData($data);
                // redirect to edit form
                return $resultRedirect->setPath(
                    '*/*/edit',
                    [
                        'banner_id' => $banner->getId(),
                        '_current' => true
                    ]
                );
            }
        }
        return $resultRedirect->setPath('bannermanager/*/');
    }

}
