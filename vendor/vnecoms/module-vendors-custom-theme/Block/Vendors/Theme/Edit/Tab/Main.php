<?php

namespace Vnecoms\VendorsCustomTheme\Block\Vendors\Theme\Edit\Tab;

use Magento\Cms\Model\Wysiwyg\Config as WysiwygConfig;

/**
 * implementing now
 *
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 */
class Main extends \Vnecoms\Vendors\Block\Vendors\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var WysiwygConfig
     */
    protected $wysiwygConfig;
    
    /**
     * @var \Magento\Framework\View\Model\PageLayout\Config\BuilderInterface
     */
    protected $pageLayoutBuilder;
    
    /**
     * @var \Vnecoms\Vendors\Model\Session
     */
    protected $vendorSession;
    
    /**
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Theme Configuration');
    }

    /**
     * @return string
     */
    public function getTabTitle()
    {
        return $this->getTabLabel();
    }

    /**
     * @return bool
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * @return $this
     */
    public function _beforeToHtml()
    {
        $this->_initForm();

        return parent::_beforeToHtml();
    }

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param WysiwygConfig $wysiwygConfig
     * @param \Magento\Framework\View\Model\PageLayout\Config\BuilderInterface $pageLayoutBuilder
     * @param \Vnecoms\Vendors\Model\Session $vendorSession
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        WysiwygConfig $wysiwygConfig,
        \Magento\Framework\View\Model\PageLayout\Config\BuilderInterface $pageLayoutBuilder,
        \Vnecoms\Vendors\Model\Session $vendorSession,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->wysiwygConfig = $wysiwygConfig;
        $this->pageLayoutBuilder = $pageLayoutBuilder;
        $this->vendorSession = $vendorSession;
    }
    
    /**
     * @return void
     */
    protected function _initForm()
    {
        $theme = $this->_coreRegistry->registry('current_theme');
        
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        /* $config = $this->wysiwygConfig->getConfig(); */
        $fieldset = $form->addFieldset('home_content_fieldset', ['legend' => __('Home Page'), 'class' => 'theme-home-content']);
        $fieldset->addField(
            'custom_theme_home_content',
            'editor',
            [
                'name' => 'theme[custom_theme][home][content]',
                'label' => __('Home Content'),
                'title' => __('Home Content'),
                'css_class' => 'admin__field-control',
                'class' => 'admin__control-textarea home_content_textarea',
                /* 'config' => $config, */
                'required' => true,
            ]
        );
        
        $form->setValues([
            'custom_theme_home_content' => $theme->getHomeContent(),
        ]);
        
        $this->_eventManager->dispatch('vnecoms_vendorscustom_theme_vendors_tab_main_after', ['form' => $form, 'object' => $theme]);
        $themeConfig = $theme->getAllConfigs();
        foreach($themeConfig as $path => $value){
            $elementId = str_replace('/','_', $path);
            $element = $form->getElement($elementId);
            if($element){
                $element->setValue($value);
            }
        }
        
        $vendorThemeConfigs = $theme->getAllConfigsByVendor($this->vendorSession->getVendor());
        
        foreach($vendorThemeConfigs as $path=>$value){
            $elementId = str_replace('/','_', $path);
            $element = $form->getElement($elementId);
            if($element){
                $element->setValue($value);
            }
        }
        
        $this->setForm($form);
    }

}
