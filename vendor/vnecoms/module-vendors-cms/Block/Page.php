<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Block;

/**
 * Class Page
 * @package Vnecoms\VendorsCms\Block
 */
class Page extends \Magento\Framework\View\Element\AbstractBlock implements
    \Magento\Framework\DataObject\IdentityInterface
{
    /**
     * @var \Vnecoms\VendorsCms\Model\Template\Filter
     */
    protected $_cmsFilter;

    /** @var \Magento\Framework\Registry  */
    protected $_coreRegistry;

    /**
     * @var \Vnecoms\VendorsCms\Model\Page
     */
    protected $_page;

    /**
     * Store manager.
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Page factory.
     *
     * @var \Vnecoms\VendorsCms\Model\PageFactory
     */
    protected $_pageFactory;

    /**
     * @var \Magento\Framework\View\Page\Config
     */
    protected $pageConfig;

    /**
     * @var \Vnecoms\VendorsPage\Helper\Data
     */
    protected $vendorPageHelper;

    /**
     * @var \Vnecoms\VendorsCms\Helper\Data
     */
    protected $cmsHelper;

    /**
     * Page constructor.
     *
     * @param \Magento\Framework\View\Element\Context    $context
     * @param \Vnecoms\VendorsCms\Model\Page             $page
     * @param \Magento\Framework\Registry                $coreRegistry
     * @param \Vnecoms\VendorsCms\Model\Template\Filter  $cmsFilter
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Vnecoms\VendorsCms\Model\PageFactory      $pageFactory
     * @param \Magento\Framework\View\Page\Config        $pageConfig
     * @param \Vnecoms\VendorsPage\Helper\Data           $vendorPageHelper
     * @param \Vnecoms\VendorsCms\Helper\Data            $cmsHelper
     * @param array                                      $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Vnecoms\VendorsCms\Model\Page $page,
        \Magento\Framework\Registry $coreRegistry,
        \Vnecoms\VendorsCms\Model\Template\Filter $cmsFilter,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Vnecoms\VendorsCms\Model\PageFactory $pageFactory,
        \Magento\Framework\View\Page\Config $pageConfig,
        \Vnecoms\VendorsPage\Helper\Data $vendorPageHelper,
        \Vnecoms\VendorsCms\Helper\Data $cmsHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        // used singleton (instead factory) because there exist dependencies on \Vnecoms\VendorsCms\Helper\Page
        $this->_page = $page;
        $this->_coreRegistry = $coreRegistry;
        $this->_cmsFilter = $cmsFilter;
        $this->_storeManager = $storeManager;
        $this->_pageFactory = $pageFactory;
        $this->pageConfig = $pageConfig;
        $this->vendorPageHelper = $vendorPageHelper;
        $this->cmsHelper = $cmsHelper;
    }

    /**
     * Retrieve Page instance.
     *
     * @return \Vnecoms\VendorsCms\Model\Page
     */
    public function getPage()
    {
        if (!$this->hasData('vendor_page')) {
            if ($this->getPageId()) {
                /** @var \Vnecoms\VendorsCms\Model\Page $page */
                $page = $this->_pageFactory->create();
                $page->setVendorId($this->getVendor()->getId())->load($this->getPageId(), 'identifier');
            } else {
                $page = $this->_page;
            }
            $this->setData('vendor_page', $page);
        }

        return $this->getData('vendor_page');
    }

    /**
     * Prepare global layout.
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $page = $this->getPage();
        $this->_addBreadcrumbs($page);
        $this->pageConfig->addBodyClass('vendor-cms-'.$page->getIdentifier());
        $metaTitle = $page->getMetaTitle();
        $this->pageConfig->getTitle()->set($metaTitle ? $metaTitle : $page->getTitle());
        $this->pageConfig->setKeywords($page->getMetaKeywords());
        $this->pageConfig->setDescription($page->getMetaDescription());

        /*$root = $this->getLayout()->getBlock('root');
        switch ($page->getPageLayout()) {
            case 'empty';
                $template = 'empty.phtml';
                break;
            case 'one_column';
                $template = '1column.phtml';
                break;
            case 'two_columns_left';
                $template = '2columns-left.phtml';
                break;
            case 'two_columns_right';
                $template = '2columns-right.phtml';
                break;
            case 'three_columns';
                $template = '3columns.phtml';
                break;
        }*/

        //  $root->setTemplate('page/'.$template);
        $pageMainTitle = $this->getLayout()->getBlock('page.main.title');
        if ($pageMainTitle) {
            // Setting empty page title if content heading is absent
            $cmsTitle = $page->getContentHeading() ?: ' ';
            $pageMainTitle->setPageTitle($this->escapeHtml($cmsTitle));
        }

        return parent::_prepareLayout();
    }

    /**
     * Prepare breadcrumbs.
     *
     * @param \Vnecoms\VendorsCms\Model\Page $page
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _addBreadcrumbs(\Vnecoms\VendorsCms\Model\Page $page)
    {
        if (
            $this->getVendor() &&
            $this->cmsHelper->getVendorConfig('page/cms/show_bread_crumb', $this->getVendor()->getId())
            && ($breadcrumbsBlock = $this->getLayout()->getBlock('breadcrumbs'))
        ) {
            $label = $this->cmsHelper->getVendorConfig('general/store_information/name', $this->getVendor()->getId());
            if (!$label) {
                $label = 'Home';
            }
            $breadcrumbsBlock->addCrumb(
                'home',
                [
                    'label' => __($label),
                    'title' => __('Go to Vendor Page'),
                    'link' => $this->getVendorPageUrl(),
                ]
            );
            $breadcrumbsBlock->addCrumb('cms_page', ['label' => $page->getTitle(), 'title' => $page->getTitle()]);
        }
    }

    /**
     * @return string|string[]
     * @throws \Exception
     */
    protected function _toHtml()
    {
        $html = $this->_cmsFilter->filter($this->getPage()->getContent());

        return $html;
    }

    /**
     * Return identifiers for produced content.
     *
     * @return array
     */
    public function getIdentities()
    {
        return [\Vnecoms\VendorsCms\Model\Page::CACHE_TAG.'_'.$this->getPage()->getId()];
    }

    /**
     * Get current vendor ID.
     *
     * @return int|string
     */
    public function getVendor()
    {
        return $this->_coreRegistry->registry('vendor');
    }

    /**
     * Get vendor page url.
     *
     * @return string
     */
    public function getVendorPageUrl()
    {
        return $this->vendorPageHelper->getUrl($this->getVendor());
    }
}
