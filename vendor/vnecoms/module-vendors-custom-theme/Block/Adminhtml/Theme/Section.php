<?php
namespace Vnecoms\VendorsCustomTheme\Block\Adminhtml\Theme;

class Section extends \Magento\Backend\Block\Template
{
    /**
     * Tabs
     *
     * @var \Magento\Config\Model\Config\Structure\Element\Iterator
     */
    protected $_tabs;
    
    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;
    
    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Config\Model\Config\Structure $configStructure
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Config\Model\Config\Structure $configStructure,
        \Magento\Framework\Registry $coreRegistry,
        array $data = []
    ) {
        $this->_tabs = $configStructure->getTabs();
        $this->coreRegistry = $coreRegistry;
        parent::__construct($context, $data);
    }
    
    /**
     * @return \Magento\Config\Model\Config\Structure\Element\Iterator
     */
    public function getTabs(){
        return $this->_tabs;
    }
    
    /**
     * @return \Vnecoms\VendorsCustomTheme\Model\Theme
     */
    public function getTheme(){
        return $this->coreRegistry->registry('current_theme');;
    }
    
    /**
     * Get Exist Section Ids
     */
    public function getExistSectionIds(){
        return $this->getTheme()->getAllSections();
    }
    
    /**
     * Get add section URL
     * 
     * @return string
     */
    public function getAddSectionUrl(){
        return $this->getUrl('vendors/theme/addSection',['theme' => $this->getTheme()->getId()]);
    }
}
