<?php

namespace Vnecoms\VendorsCustomTheme\Block\Vendors\Theme;

class Edit extends \Vnecoms\Vendors\Block\Vendors\Widget\Form\Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Initialize form
     * Add standard buttons
     * Add "Save and Apply" button
     * Add "Save and Continue" button
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'Vnecoms_VendorsCustomTheme';
        $this->_controller = 'Vendors_Theme';
        
        parent::_construct();
        $this->updateButton('save', 'label', __('Save Theme'));
        $this->updateButton('reset', 'label', __('Reset to default'));
        $this->updateButton('reset', 'class', 'btn btn-default btn-danger fa fa-refresh');
        $this->updateButton('reset', 'onclick', 'setLocation(\'' . $this->getResetUrl() . '\')');
        
        $this->removeButton('delete');

        return $this;
    }

    /**
     * Getter for form header text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        $theme = $this->coreRegistry->registry('current_theme');
        if ($theme->getId()) {
            return __("Edit Theme #%s", $this->escapeHtml($theme->getId()));
        } else {
            return __('New Theme');
        }
    }
    
    /**
     * @see \Magento\Backend\Block\Widget\Form\Container::getBackUrl()
     */
    public function getBackUrl(){
        return $this->getUrl('config/index/edit',['section' => 'custom_theme']);
    }
    
    /**
     * @see \Magento\Backend\Block\Widget\Form\Container::getBackUrl()
     */
    public function getResetUrl(){
        return $this->getUrl('theme/index/reset',['id' => $this->coreRegistry->registry('current_theme')->getId()]);
    }
}
