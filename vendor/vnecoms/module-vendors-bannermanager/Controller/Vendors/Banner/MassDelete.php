<?php

namespace Vnecoms\BannerManager\Controller\Vendors\Banner;


use Vnecoms\BannerManager\Controller\Vendors\Banner;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class MassDelete
 * @category Vnecoms
 * @package  Vnecoms_BannerManager
 * @module   BannerManager
 * @author   Vnecoms Developer Team
 */
class MassDelete extends Banner
{

    /**
     * Mass Deletion action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     * @throws \Magento\Framework\Exception\LocalizedException|\Exception
     */
    public function execute()
    {

        $collection = $this->filter->getCollection($this->_bannerCollectionFactory->create());
        $collectionSize = $collection->getSize();

        foreach ($collection as $banner) {
            $banner->delete();
        }

        $this->messageManager->addSuccess(__('A total of %1 record(s) have been deleted.', $collectionSize));

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');

    }
}
