<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Helper;

use Magento\Framework\App\Action\Action;

/**
 * CMS Page Helper.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
class Page extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * CMS no-route config path.
     */
    const XML_PATH_404_PAGE = 'cms/vendor_configs/404_page';

    /**
     * CMS home page config path.
     */
    const XML_PATH_HOME_PAGE = 'page/cms/home_page';

    /**
     * Design package instance.
     *
     * @var \Magento\Framework\View\DesignInterface
     */
    protected $_design;

    /**
     * @var \Vnecoms\VendorsCms\Model\Page
     */
    protected $_page;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;

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
     * @var \Magento\Framework\Escaper
     */
    protected $_escaper;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    protected $vendorConfigHelper;

    protected $_coreRegistry;

    protected $vendorHelperData;

    /**
     * Page constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Vnecoms\VendorsCms\Model\Page $page
     * @param \Magento\Framework\View\DesignInterface $design
     * @param \Vnecoms\VendorsCms\Model\PageFactory $pageFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Vnecoms\VendorsConfig\Helper\Data $vendorConfigHelper
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Vnecoms\VendorsPage\Helper\Data $vendorHelperData
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Vnecoms\VendorsCms\Model\Page $page,
        \Magento\Framework\View\DesignInterface $design,
        \Vnecoms\VendorsCms\Model\PageFactory $pageFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Escaper $escaper,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Vnecoms\VendorsConfig\Helper\Data $vendorConfigHelper,
        \Magento\Framework\Registry $coreRegistry,
        \Vnecoms\VendorsPage\Helper\Data $vendorHelperData
    ) {
        $this->messageManager = $messageManager;
        $this->_page = $page;
        $this->_design = $design;
        $this->_pageFactory = $pageFactory;
        $this->_storeManager = $storeManager;
        $this->_localeDate = $localeDate;
        $this->_escaper = $escaper;
        $this->resultPageFactory = $resultPageFactory;
        $this->vendorConfigHelper = $vendorConfigHelper;
        $this->_coreRegistry = $coreRegistry;
        $this->vendorHelperData = $vendorHelperData;
        parent::__construct($context);
    }

    /**
     * Return result CMS page.
     *
     * @param Action $action
     * @param null|string   $pageId
     *
     * @return \Magento\Framework\View\Result\Page|bool
     */
    public function prepareResultPage(Action $action, $pageId = null)
    {
        if ($pageId !== null && $pageId !== $this->_page->getId()) {
            $delimiterPosition = strrpos($pageId, '|');
            if ($delimiterPosition) {
                $pageId = substr($pageId, 0, $delimiterPosition);
            }
            $this->_page->setStoreId($this->_storeManager->getStore()->getId());
            if (!$this->_page->load($pageId)) {
                return false;
            }
        }

        if (!$this->_page->getId()) {
            return false;
        }

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->addHandle('vendor_page');
        $this->setLayoutType($resultPage);
        $resultPage->addHandle('vendorscms_page_view');
        $resultPage->addPageLayoutHandles(['id' => $this->_page->getIdentifier()]);

        $this->_eventManager->dispatch(
            'vendor_cms_page_render',
            ['page' => $this->_page, 'controller_action' => $action]
        );

        $contentHeadingBlock = $resultPage->getLayout()->getBlock('page_content_heading');
        if ($contentHeadingBlock) {
            $contentHeading = $this->_escaper->escapeHtml($this->_page->getContentHeading());
            $contentHeadingBlock->setContentHeading($contentHeading);
        }

        return $resultPage;
    }


    /**
     * Retrieve page direct URL
     *
     * @param string $pageId
     * @return string
     */
    public function getPageUrl($pageId = null)
    {
        /** @var \Magento\Cms\Model\Page $page */
        $page = $this->_pageFactory->create();
        if ($pageId !== null) {
            $page->setStoreId($this->_storeManager->getStore()->getId());
            $page->load($pageId);
        }

        if (!$page->getId()) {
            return null;
        }

        return $this->vendorHelperData->getUrl($this->getVendorId(), $page->getIdentifier());
    }


    /**
     * Set layout type.
     *
     * @param \Magento\Framework\View\Result\Page $resultPage
     *
     * @return \Magento\Framework\View\Result\Page
     */
    protected function setLayoutType($resultPage)
    {
        if ($this->_page->getPageLayout()) {
            $handle = $this->_page->getPageLayout();
            $resultPage->getConfig()->setPageLayout($handle);
        }

        return $resultPage;
    }

    /**
     * @return string
     */
    public function getVendorHomePage()
    {
        return $this->vendorConfigHelper->getVendorConfig(self::XML_PATH_HOME_PAGE, $this->getVendorId());
    }

    /**
     * @return string
     */
    public function getVendorNoRoute()
    {
        return $this->vendorConfigHelper->getVendorConfig(self::XML_PATH_404_PAGE, $this->getVendorId());
    }

    /**
     * @return int
     */
    public function getVendorId()
    {
        return \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Magento\Framework\Registry')->registry('vendor')->getId();
    }
}
