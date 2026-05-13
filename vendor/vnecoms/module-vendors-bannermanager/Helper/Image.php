<?php

namespace Vnecoms\BannerManager\Helper;

/**
 * Class Image
 * @category Vnecoms
 * @package  Vnecoms_BannerManager
 * @module   BannerManager
 * @author   Vnecoms Developer Team
 */
class Image extends \Magento\Catalog\Helper\Image
{
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Catalog\Model\Product\ImageFactory $productImageFactory,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\View\ConfigInterface $viewConfig
    ) {
        parent::__construct($context, $productImageFactory, $assetRepo, $viewConfig);
    }

}
