<?php
namespace Vnecoms\VendorsPage\Block\System\Config\Form\Field;

class Editor extends \Vnecoms\VendorsConfig\Block\System\Config\Form\Field
{
    /**
     * @var \Magento\Cms\Model\Wysiwyg\Config
     */
    protected $_wysiwygConfig;
    
    /**
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig,
        array $data = []
    ) {
        $this->_wysiwygConfig = $wysiwygConfig;
        parent::__construct($context, $data);
    }
    
    
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $wysiwygConfig = $this->_wysiwygConfig->getConfig();
        $wysiwygConfig['add_variables'] = false;
        $wysiwygConfig['add_widgets'] = false;
        $wysiwygConfig['plugins'] = '';
        if($wysiwygConfig instanceof \Magento\Framework\DataObject){
            $settings = $wysiwygConfig->getSettings();
            if(!is_array($settings)){
                $settings = [];                
            }
            $settings['plugins'] = '';
            $settings['toolbar1'] = 'formatselect | styleselect | fontsizeselect | forecolor backcolor | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent';
            $wysiwygConfig->setSettings($settings);
        }
        
        
        $element->setTheme('simple');
        $element->setWysiwyg(true);
        $element->setConfig($wysiwygConfig);
        return parent::_getElementHtml($element);
    }
}
