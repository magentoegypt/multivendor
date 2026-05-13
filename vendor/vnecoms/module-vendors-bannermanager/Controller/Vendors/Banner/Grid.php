<?php

namespace Vnecoms\BannerManager\Controller\Vendors\Banner;

use Vnecoms\BannerManager\Controller\Vendors\Banner;

/**
 * Class Grid
 * @category Vnecoms
 * @package  Vnecoms_BannerManager
 * @module   BannerManager
 * @author   Vnecoms Developer Team
 */
class Grid extends Banner
{
    /**
     * Grid Action
     *
     * @return \Magento\Framework\View\Result\Layout
     */
    public function execute()
    {
        return $this->_resultLayoutFactory->create();
    }
}
