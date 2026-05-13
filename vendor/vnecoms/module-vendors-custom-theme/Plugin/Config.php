<?php
namespace Vnecoms\VendorsCustomTheme\Plugin;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{    
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;
    
    /**
     * @param \Vnecoms\VendorsProduct\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\Registry $coreRegistry
    ) {
        $this->_coreRegistry = $coreRegistry;
        return $this;
    }
    
    /**
     * Rewrite get config value
     * 
     * @param \Magento\Config\App\Config\Type\System $subject
     * @param callable $proceed
     * @param string $path
     * @param string $scope
     * @param null|string $scopeCode
     * @return unknown
     */
    public function aroundGetValue(
        \Magento\Framework\App\Config $subject,
        callable $proceed,
        $path,
        $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        $scopeCode = null
    ) {
        if($theme = $this->_coreRegistry->registry('vendor_custom_theme')){
            $vendor = $this->_coreRegistry->registry('vendor');
            $allVendorConfigs = $theme->getAllConfigsByVendor($vendor);
            if(isset($allVendorConfigs[$path])){
                return $allVendorConfigs[$path];
            }
            
            $allConfigs = $theme->getAllConfigs();
            if(isset($allConfigs[$path])){
                return $allConfigs[$path];
            }
        }
        return $proceed($path, $scope, $scopeCode);
    }

}
