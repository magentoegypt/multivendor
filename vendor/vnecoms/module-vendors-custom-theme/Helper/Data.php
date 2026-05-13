<?php
namespace Vnecoms\VendorsCustomTheme\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class Data extends AbstractHelper
{
    const XML_PATH_ENABLE_REGISTRATION_FORM = 'vendors/custom_theme/register_form';
    const XML_PATH_VENDOR_SELECTED_THEME    = 'custom_theme/general/theme';
    
    
    const XML_PATH_THEME_CONFIG_HOME_CONTENT = 'custom_theme/home/content';

    const XML_PATH_VENDOR_CUSTOM_THEME  = 'custom_theme/enabled';

    /**
     * @var \Vnecoms\VendorsConfig\Helper\Data
     */
    protected $configHelper;
		
    /**
		 * @var string[]
		 */
		protected $notAllowedConfigGroupPaths;
		
		 /**
		 * @var string[]
		 */
		protected $notAllowedConfigFieldPaths;
		
    /**
     * @param Context $context
     * @param \Vnecoms\VendorsConfig\Helper\Data $configHelper
		 * @param string[] $notAllowedConfigGroupPaths
		 * @param string[] $notAllowedConfigFieldPaths
     */
    public function __construct(
        Context $context,
        \Vnecoms\VendorsConfig\Helper\Data $configHelper,
				$notAllowedConfigGroupPaths = [],
				$notAllowedConfigFieldPaths = []
    ) {
        $this->configHelper = $configHelper;
				$this->notAllowedConfigGroupPaths = $notAllowedConfigGroupPaths;
				$this->notAllowedConfigFieldPaths = $notAllowedConfigFieldPaths;
        parent::__construct($context);
    }
    
    /**
     * @return boolean
     */
    public function isEnableForRegister(){
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_ENABLE_REGISTRATION_FORM);
    }
    
    /**
     * Get vendor theme
     * 
     * @param int|\Vnecoms\Vendors\Model\Vendor $vendorId
     */
    public function getVendorTheme($vendorId){
        $om = ObjectManager::getInstance();
        if($vendorId instanceof \Vnecoms\Vendors\Model\Vendor){
            $vendor = $vendorId;
            $vendorId = $vendorId->getId();
        }else{
            $vendor = $om->create('Vnecoms\Vendors\Model\Vendor')->load($vendorId);
        }

        if (
            class_exists('Vnecoms\VendorsGroup\Helper\Data')
        ){
            /** @var \Vnecoms\VendorsGroup\Helper\Data $groupHelper */
            $groupHelper = $om->create('Vnecoms\VendorsGroup\Helper\Data');
            if(!$groupHelper->getConfig(self::XML_PATH_VENDOR_CUSTOM_THEME, $vendor->getGroupId())){
                return '';
            }
        }

        return $this->configHelper->getVendorConfig(self::XML_PATH_VENDOR_SELECTED_THEME, $vendorId);
    }
		
		/**
		 * @return string[]
		 */
		public function getNotAllowedConfigGroupPaths(){
				return $this->notAllowedConfigGroupPaths;
		}
		
		/**
		 * @return string[]
		 */
		public function getNotAllowedConfigFieldPaths(){
				return $this->notAllowedConfigFieldPaths;
		}
}
