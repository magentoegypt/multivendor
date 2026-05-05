<?php 
namespace MagentoEgypt\PortoExtend\Plugin\Model;

class Theme
{
    protected $_coreRegistry;
	protected $_messageManager;
    protected $_cssconfigData;
    protected $_layoutManager;
    
    public function __construct(
        \Smartwave\Porto\Helper\Cssconfig $cssconfigData,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\View\LayoutInterface $layoutManager,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->_cssconfigData = $cssconfigData;
        $this->_coreRegistry = $coreRegistry;
        $this->_layoutManager = $layoutManager;
        $this->_messageManager = $messageManager;
    }

    public function beforeSave($subject)
    {
        $groups = $subject->getGroups();
        if(!empty($groups)) {    
            foreach ($groups as $sectionId => $sectionData) {
                foreach($sectionData as $groupId => $groupData){
                    foreach ($groupData['fields'] as $fieldId => $fieldData) {
                        $fieldsetData[$fieldId] = !empty($fieldData['value']) ? $fieldData['value'] : null;
                    }
                }
            }
            $subject->setGroups($groups);
        }
        return [];
    }

	public function afterSave($subject, $return)
	{
		$themeId = $subject->getId();
		if($themeId>0) {
            $groups = $subject->getGroups();
            if(!empty($groups)) {
                $this->_coreRegistry->register('vendor_custom_theme', $subject);
                $this->generateCss('settings',$themeId);
                $this->generateCss('design',$themeId);
            }
        }
		return $return;
	}

	protected function generateCss($type, $storeCode)
	{
		$str1 = '_'.$storeCode;
        $str2 = $type.$str1.'.css';
        $str3 = $this->_cssconfigData->getCssConfigDir().$str2;
        $str4 = 'porto/css/'.$type.'.phtml';

        try {
            $block = $this->_layoutManager->createBlock('Smartwave\Porto\Block\Template')->setData('area','frontend')->setTemplate($str4)->toHtml();
            // if($type == 'design') die($block);
            if(!file_exists($this->_cssconfigData->getCssConfigDir())) {
                @mkdir($this->_cssconfigData->getCssConfigDir(), 0777);
            }
            $file = @fopen($str3,"w+");
            @flock($file, LOCK_EX);
            @fwrite($file,$block);
            @flock($file, LOCK_UN);
            @fclose($file);
            if(empty($block)) {
                throw new \Exception( __("Template file is empty or doesn't exist: ".$str4) );
            }
        } catch (\Exception $e) {
            $this->_messageManager->addError(__('Failed generating CSS file: '.$str2.' in '.$this->_cssconfigData->getCssConfigDir()).'<br/>Message: '.$e->getMessage());
        }
	}
}