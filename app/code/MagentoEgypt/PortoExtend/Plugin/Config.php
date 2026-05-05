<?php
namespace MagentoEgypt\PortoExtend\Plugin;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Config extends \Vnecoms\VendorsCustomTheme\Plugin\Config
{
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
            $arrPath = explode('/',$path);
            if($vendor = $this->_coreRegistry->registry('vendor'))
            {
                $allVendorConfigs = $theme->getAllConfigsByVendor($vendor);
                if(count($allVendorConfigs)>0 && count($arrPath)<3) {
                	$newAllVendorConfigs = $this->getAllSubConfig($path,$allVendorConfigs);
                	if(count($newAllVendorConfigs)>0) {
                		$mainConfig = $proceed($path, $scope, $scopeCode);
                		return array_merge($mainConfig,$newAllVendorConfigs);
                	}
                } else if(isset($allVendorConfigs[$path])){
                    return $allVendorConfigs[$path];
                }
            }
            
            $allConfigs = $theme->getAllConfigs();
            if(count($allConfigs)>0 && count($arrPath)<3) {
            	$newAllConfigs = $this->getAllSubConfig($path,$allConfigs);
            	if(count($newAllConfigs)>0) {
            		$mainConfig = $proceed($path, $scope, $scopeCode);
                    // var_dump($newAllConfigs);
                    return $newAllConfigs;
            	}
            } else if(array_key_exists($path,$allConfigs)){
                return $allConfigs[$path] ?? '';
            }
        }
        return $proceed($path, $scope, $scopeCode);
    }

    private function getAllSubConfig($path,$allConfigs)
    {
    	$finalConfig = [];
    	foreach($allConfigs as $key => $value) {
    		if(strpos($key, $path) !== false) {
    			$newKey = str_replace($path, '', $key);
    			$newKey = trim($newKey,'/');
                $newKey = explode('/', $newKey);
    			if(count($newKey) == 3) $finalConfig[$newKey[0]][$newKey[1]][$newKey[2]] = $value;
                else if(count($newKey) == 2) $finalConfig[$newKey[0]][$newKey[1]] = $value;
                else $finalConfig[$newKey[0]] = $value;
    		}
    	}
    	return $finalConfig;
    }
}