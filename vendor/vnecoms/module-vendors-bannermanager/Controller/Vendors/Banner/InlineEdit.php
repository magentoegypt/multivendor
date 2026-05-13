<?php

namespace Vnecoms\BannerManager\Controller\Vendors\Banner;

use Vnecoms\BannerManager\Controller\Vendors\Banner;

/**
 * Class InlineEdit
 * @category Vnecoms
 * @package  Vnecoms_BannerManager
 * @module   BannerManager
 * @author   Vnecoms Developer Team
 */
class InlineEdit extends Banner
{

    /**
     * Execute page
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->jsonFactory->create();
        $error = false;
        $messages = [];

        if ($this->getRequest()->getParam('isAjax')) {
            $postItems = $this->getRequest()->getParam('items', []);
            if (!count($postItems)) {
                $messages[] = __('Please correct the data sent.');
                $error = true;
            } else {
                foreach (array_keys($postItems) as $bannerId) {

                    $banner = $this->_bannerCollectionFactory->getById($bannerId);
                    try {
                        $banner->setData(array_merge($banner->getData(), $postItems[$bannerId]));
                        $this->_bannerCollectionFactory->save($banner);
                    } catch (\Exception $e) {
                        $messages[] = $e->getMessage();
                        $error = true;
                    }
                }
            }
        }

        return $resultJson->setData([
            'messages' => $messages,
            'error' => $error
        ]);
    }
}
