<?php
/**
 *
 * Created by Vnecoms Core Team.
 *
 * @category  Vnecoms
 * @package   Vnecoms_ModuleName
 * @author    Vnecoms
 * @created_by mrtuvn
 * @date: 24/04/2017
 * @time: 15:24
 * @copyright Copyright (c) 2012-2017 Vnecoms
 * @license   https://www.vnecoms.com
 */

namespace Vnecoms\BannerManager\Block\Cms\App;


$om = \Magento\Framework\App\ObjectManager::getInstance();
$moduleManager = $om->create('Magento\Framework\Module\Manager');

if($moduleManager->isEnabled('Vnecoms_VendorsCms')) {

    class Banner extends \Vnecoms\BannerManager\Block\Banner implements \Vnecoms\VendorsCms\Block\App\AppInterface
    {


    }
} else {
    class Banner {}
}