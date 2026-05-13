<?php

namespace Vnecoms\VendorsCustomTheme\Controller\ThemePage;

use Magento\Framework\Registry;
use \Magento\Framework\App\Action\Context;

class View extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Registry
     */
    protected $coreRegistry;
    
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;
    
    /**
     * @var \Vnecoms\VendorsPage\Helper\Data
     */
    protected $pageHelper;
    
    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Vnecoms\VendorsPage\Helper\Data $pageHelper
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Vnecoms\VendorsPage\Helper\Data $pageHelper
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->resultPageFactory = $resultPageFactory;
        $this->pageHelper = $pageHelper;
        parent::__construct($context);
    }
    
    /**
     * View CMS page action.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->addHandle('vendor_page');
        /* $resultPage->getConfig()->setPageLayout(''); */
        $resultPage->addHandle('vendorstheme_page_view');
        $currentTheme = $this->coreRegistry->registry('vendor_custom_theme');
        if($layoutUpdate = $currentTheme->getHomeLayoutXml()){
            $resultPage->getLayout()->getUpdate()->addUpdate($layoutUpdate);
        }
        $pageConfig = $resultPage->getConfig();
        $pageConfig->setPageLayout($currentTheme->getHomeLayout());

        $vendor = $this->getVendor();
        $title = $this->pageHelper->getMetaTitle($vendor->getId());
        $title = $title?$title:__("%1's home page", ucfirst($vendor->getVendorId()));
        $pageConfig->getTitle()->set($title);
        
        $description = $this->pageHelper->getMetaDescription($vendor->getId());
        $pageConfig->setDescription($description);
        
        $keywords = $this->pageHelper->getMetaKeywords($vendor->getId());
        if ($keywords) {
            $pageConfig->setKeywords($keywords);
        }
        
        
        return $resultPage;
    }
    
    /**
     * Get vendor
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor(){
        return $this->coreRegistry->registry('vendor');
    }
}
