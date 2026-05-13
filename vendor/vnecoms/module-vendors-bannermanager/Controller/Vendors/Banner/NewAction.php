<?php

namespace Vnecoms\BannerManager\Controller\Vendors\Banner;


use Vnecoms\BannerManager\Controller\Vendors\Banner;

/**
 * Class New Action
 * @category Vnecoms
 * @package  Vnecoms_BannerManager
 * @module   BannerManager
 * @author   Vnecoms Developer Team
 */
class NewAction extends Banner
{
    /**
     * New Action
     *
     * @param void
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultForward = $this->_resultForwardFactory->create();
        return $resultForward->forward('edit');
    }
}
