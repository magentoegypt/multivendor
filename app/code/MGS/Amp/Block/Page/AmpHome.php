<?php

namespace MGS\Amp\Block\Page;

use Magento\Framework\View\Element\Template;

class AmpHome extends Template {
	
	/**
     * @var \Magento\Cms\Model\Template\FilterProvider
     */
    protected $_filterProvider;

    /**
     * @var MGS\Amp\Helper\Config
     */
    protected $_configHelper;
	
    /**
     * @var Magento\Cms\Model\PageFactory
     */
    protected $_pageFactory;

    /**
     * Construct
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Zemez\Amp\Helper\Data $helper,
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \MGS\Amp\Helper\Config $configHelper,
		\Magento\Cms\Model\PageFactory $pageFactory,
		\Magento\Cms\Model\Template\FilterProvider $filterProvider,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_configHelper = $configHelper;
		$this->_filterProvider = $filterProvider;
		$this->_pageFactory = $pageFactory;
    }
	
	public function getContentAmp(){
		$content = $this->getCmsPageContent();
		return $this->_filterProvider->getPageFilter()->filter($this->replaceTeamplate($content));
	}
	
	protected function getCmsForAmp(){
		return $this->_configHelper->getStoreConfig('mgs_amp/general/cms_home_mobile');
	}
	
	protected function getCmsPageContent(){
		$csmPageContent = '';
		$cmsPage = $this->getCmsForAmp();
		if ($cmsPage) {
			$csmPageContent = $this->_pageFactory->create()->load($cmsPage, 'identifier')->getContent();
		}
		
		return $csmPageContent;
	}
	
	protected function replaceTeamplate($content){
		/* Slider Block */
		$content = str_replace('template="widget/owl_slider.phtml"','template="MGS_Amp::MGS_Fbuilder/widget/owl_slider.phtml"',$content);
		/* Promobanner Block */
		$content = str_replace('template="widget/promobanner.phtml"','template="MGS_Amp::MGS_Fbuilder/widget/promobanner.phtml"',$content);
		/* Product Block */
		$content = str_replace('template="products/grid.phtml"','template="MGS_Amp::MGS_Fbuilder/products/grid.phtml"',$content);
		/* Newsletter Block */
		$content = str_replace('template="Magento_Newsletter::subscribe.phtml"','template="MGS_Amp::Magento_Newsletter/subscribe.phtml"',$content);
		
		return $content;
	}
}