<?php
/**
 * Created by PhpStorm.
 * User: mrtuvn
 * Date: 20/12/2016
 * Time: 17:57.
 */

namespace Vnecoms\VendorsCms\Helper;

class Data extends \Vnecoms\Vendors\Helper\Data
{
    /**
     * Data constructor.
     *
     * @param \Magento\Framework\App\Helper\Context       $context
     * @param \Magento\Framework\App\Route\Config         $routeConfig
     * @param \Magento\Framework\Locale\ResolverInterface $locale
     * @param \Vnecoms\Vendors\Model\UrlInterface         $backendUrl
     * @param \Magento\Backend\Model\Auth                 $auth
     * @param \Vnecoms\Vendors\App\Area\FrontNameResolver $frontNameResolver
     * @param \Magento\Framework\Math\Random              $mathRandom
     * @param \Vnecoms\VendorsConfig\Helper\Data          $configHelper
     * @param array                                       $notSaveVendorAttribute
     * @param array                                       $modulesUseTemplateFromAdminhtml
     * @param array                                       $blocksUseTemplateFromAdminhtml
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Route\Config $routeConfig,
        \Magento\Framework\Locale\ResolverInterface $locale,
        \Vnecoms\Vendors\Model\UrlInterface $backendUrl,
        \Magento\Backend\Model\Auth $auth,
        \Vnecoms\Vendors\App\Area\FrontNameResolver $frontNameResolver,
        \Magento\Framework\Math\Random $mathRandom,
        \Vnecoms\VendorsConfig\Helper\Data $configHelper,
        array $notSaveVendorAttribute = [],
        array $modulesUseTemplateFromAdminhtml = [],
        array $blocksUseTemplateFromAdminhtml = []
    ) {
        parent::__construct($context, $routeConfig, $locale, $backendUrl, $auth, $frontNameResolver, $mathRandom, $configHelper, $notSaveVendorAttribute, $modulesUseTemplateFromAdminhtml, $blocksUseTemplateFromAdminhtml);
    }

    public function getConfig($key, $store = null)
    {
        $result = $this->scopeConfig->getValue(
            $key,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );

        return $result;
    }

    /**
     * Get vendor config.
     *
     * @param $key
     * @param $vendor
     *
     * @return string|int
     */
    public function getVendorConfig($key, $vendor)
    {
        return $this->_configHelper->getVendorConfig($key, $vendor);
    }

    /**
     * get enable status module.
     *
     * @return string|int
     */
    public function isEnabled()
    {
        return $this->getConfig('vendors/cms/active');
    }

    public function showAppTitle()
    {
        return $this->getConfig('vendors/cms/show_app_title');
    }
}
