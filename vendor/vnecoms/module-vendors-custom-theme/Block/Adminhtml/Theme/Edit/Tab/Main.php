<?php
namespace Vnecoms\VendorsCustomTheme\Block\Adminhtml\Theme\Edit\Tab;

use Magento\Backend\Block\Widget\Form;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Cms\Model\Wysiwyg\Config as WysiwygConfig;

class Main extends Generic implements TabInterface
{
    
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;
    
    /**
     * @var WysiwygConfig
     */
    protected $wysiwygConfig;
    
    /**
     * @var \Magento\Framework\View\Model\PageLayout\Config\BuilderInterface
     */
    protected $pageLayoutBuilder;
    
   /**
    * @param \Magento\Backend\Block\Template\Context $context
    * @param \Magento\Framework\Registry $registry
    * @param \Magento\Framework\Data\FormFactory $formFactory
    * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
    * @param WysiwygConfig $wysiwygConfig
    * @param array $data
    */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        WysiwygConfig $wysiwygConfig,
        \Magento\Framework\View\Model\PageLayout\Config\BuilderInterface $pageLayoutBuilder,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_date = $date;
        $this->wysiwygConfig = $wysiwygConfig;
        $this->pageLayoutBuilder = $pageLayoutBuilder;
        
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare content for tab
     *
     * @return \Magento\Framework\Phrase
     * @codeCoverageIgnore
     */
    public function getTabLabel()
    {
        return __('Theme Information');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     * @codeCoverageIgnore
     */
    public function getTabTitle()
    {
        return __('Theme Information');
    }

    /**
     * Returns status flag about this tab can be showed or not
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Returns status flag about this tab hidden or not
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * @return Form
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('current_theme');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();             
        
        $fieldset = $form->addFieldset('theme_info_fieldset', ['legend' => __('Theme Information'), 'class' => 'theme-info']);

        $isDisabled = false;
        $fieldset->addField(
            'title',
            'text',
            [
                'name' => 'theme[title]',
                'label' => __('Title'),
                'title' => __('Title'),
                'required' => true,
            ]
        );
        
        $fieldset->addField(
            'base_theme_id',
            'select',
            [
                'name' => 'theme[base_theme_id]',
                'label' => __('Base Theme'),
                'title' => __('Base Theme'),
                'options' => ObjectManager::getInstance()->create('Vnecoms\VendorsCustomTheme\Model\Source\BaseTheme')->getOptionArray(),
            ]
        );
        $fieldset->addField(
            'preview_image',
            'image',
            [
                'name' => 'theme[preview_image]',
                'label' => __('Preview Image'),
                'title' => __('Preview Image'),
                'required' => true,
            ]
        );
        
        $fieldset->addField(
            'status',
            'select',
            [
                'name' => 'theme[status]',
                'label' => __('Status'),
                'title' => __('Status'),
                'required' => true,
                'options' => [
                    '0' => __("Disable"),
                    '1' => __("Enable"),
                ]
            ]
        );
        
        $fieldset = $form->addFieldset('home_content_fieldset', ['legend' => __('Home Page'), 'class' => 'theme-home-content']);
        $fieldset->addField(
            'home_content',
            'editor',
            [
                'name' => 'theme[home_content]',
                'label' => __('Default Home Content'),
                'title' => __('Default Home Content'),
                'config' => $this->wysiwygConfig->getConfig(),
                'required' => true,
            ]
        );
                
        $pageLayout = $this->pageLayoutBuilder->getPageLayoutsConfig()->toOptionArray();
        $fieldset->addField(
            'home_layout',
            'select',
            [
                'name' => 'theme[home_layout]',
                'label' => __('Layout'),
                'required' => false,
                'values' => $pageLayout,
            ]
        );
        
        $fieldset->addField(
            'home_layout_xml',
            'editor',
            [
                'name' => 'theme[home_layout_xml]',
                'label' => __('Layout Update XML'),
                'title' => __('Layout Update XML'),
                'required' => false,
            ]
        );
        
        $data = $model->getData();

        $form->setValues($data);

        $this->_eventManager->dispatch('vnecoms_vendorscustom_theme_tab_main_after', ['form' => $form, 'object' => $model, 'tab' => $this]);
        
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
