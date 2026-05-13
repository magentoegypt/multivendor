<?php

namespace Vnecoms\VendorsCustomTheme\Block;

class Theme extends \Magento\Framework\View\Element\AbstractBlock{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var array
     */
    protected $filters;

    /**
     * @param \Magento\Framework\View\Element\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $filters
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        array $filters = [],
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->filters = $filters;
        $this->coreRegistry = $coreRegistry;
    }

    /**
     * Prepare HTML content.
     *
     * @return string
     */
    protected function _toHtml()
    {
        $theme = $this->getCurrentTheme();
        $vendorConfig = $theme->getAllConfigsByVendor($this->getVendor());
        $configPath = \Vnecoms\VendorsCustomTheme\Helper\Data::XML_PATH_THEME_CONFIG_HOME_CONTENT;
        $html = isset($vendorConfig[$configPath]) ? $vendorConfig[$configPath] : $this->getCurrentTheme()->getHomeContent();
        foreach($this->filters as $filter){
            if($filter instanceof \Laminas\Filter\FilterInterface){
                $html = $filter->filter($html);
            }
        }
        return $html;
    }

    /**
     * Return identifiers for produced content.
     *
     * @return array
     */
    public function getIdentities()
    {
        return ['vnecoms_vendorstheme_vendor_'.$this->getVendor()->getId()];
    }

    /**
     * Get current theme
     *
     * @return \Vnecoms\VendorsCustomTHeme\Model\Theme
     */
    public function getCurrentTheme(){
        return $this->coreRegistry->registry('vendor_custom_theme');
    }

    /**
     * Get current vendor ID.
     *
     * @return int|string
     */
    public function getVendor()
    {
        return $this->coreRegistry->registry('vendor');
    }

}
