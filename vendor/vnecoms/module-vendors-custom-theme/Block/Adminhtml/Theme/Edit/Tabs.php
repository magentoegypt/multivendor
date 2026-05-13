<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCustomTheme\Block\Adminhtml\Theme\Edit;

class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /** @var string */
    protected $_template = 'Magento_Backend::widget/tabs.phtml';
    
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;
    
    /**
     * @var \Magento\Config\Model\Config\Structure
     */
    protected $_configStructure;
    
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('theme_edit_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Theme'));
    }

    /**
     * 
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Config\Model\Config\Structure $configStructure
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Config\Model\Config\Structure $configStructure,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_configStructure = $configStructure;
        
        parent::__construct($context, $jsonEncoder, $authSession, $data);
    }
    
    protected function _prepareLayout(){
        /** @var \Vnecoms\VendorsCustomTheme\Model\Theme */
        $theme = $this->_coreRegistry->registry('current_theme');
        $this->addTab('main_section', 'theme_edit_tab_main');
        
        foreach($theme->getAllSections() as $sectionId){
            $section = $this->_configStructure->getElement($sectionId);
            $configBlock = $this->getLayout()->createBlock('Vnecoms\VendorsCustomTheme\Block\Adminhtml\Theme\Edit\Tab\Config')
                ->setSection($section)
                ->setSectionId($sectionId);
            $this->setChild('tab_'.$section->getId(), $configBlock);
            
            $this->addTabAfter($section->getId(), 'tab_'.$section->getId(),'main_section');
        }
        
        $this->setActiveTab(
            $this->getRequest()->getParam('section')?$this->getRequest()->getParam('section'):'main_section'
        );
        
        return parent::_prepareLayout();
    }
}
