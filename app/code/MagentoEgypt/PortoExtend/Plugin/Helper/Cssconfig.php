<?php 
namespace MagentoEgypt\PortoExtend\Plugin\Helper;

class Cssconfig
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;
    
    /**
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Framework\Registry $registry
    ){
        $this->_registry = $registry;
    }

    public function afterGetSettingsFile($subject, $file)
    {
        $theme = $this->_registry->registry('vendor_custom_theme');
        if(!empty($theme)) {
        	$id = $theme->getId();
        	if($id>0) {
	        	$arrfile = explode('settings_',$file);
	        	$file = $arrfile[0].'settings_'.$id.'.css';
        	}
        }
        return $file;
    }

    public function afterGetDesignFile($subject, $file)
    {
        $theme = $this->_registry->registry('vendor_custom_theme');
        if(!empty($theme)) {
        	$id = $theme->getId();
        	if($id>0) {
	        	$arrfile = explode('design_',$file);
	        	$file = $arrfile[0].'design_'.$id.'.css';
        	}
        }
        return $file;
    }
}