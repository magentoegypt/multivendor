<?php
/**
 * Created by PhpStorm.
 * User: mrtuvn
 * Date: 22/12/2016
 * Time: 16:48.
 */

namespace Vnecoms\VendorsCms\Observer;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Registry;
use Vnecoms\VendorsCms\Model\Page;

class ProcessRouterMatchObserver implements ObserverInterface
{
    /** @var  \Vnecoms\VendorsCms\Model\Page */
    protected $cmsPage;

    /** @var  Registry */
    protected $_coreRegistry;

    /** @var \Vnecoms\VendorsCms\Helper\Data  */
    protected $_vendorCmsHelper;

    /** @var RequestInterface  */
    protected $_request;

    /** @var \Vnecoms\VendorsCms\Model\PageFactory  */
    protected $_cmsPageFactory;

    /**
     * ProcessRouterMatchObserver constructor.
     * @param Page $cmsPage
     * @param Registry $coreRegistry
     * @param RequestInterface $request
     * @param \Vnecoms\VendorsCms\Model\PageFactory $pageFactory
     * @param \Vnecoms\VendorsCms\Helper\Data $cmsHelper
     */
    public function __construct(
        \Vnecoms\VendorsCms\Model\Page $cmsPage,
        Registry $coreRegistry,
        \Magento\Framework\App\RequestInterface $request,
        \Vnecoms\VendorsCms\Model\PageFactory $pageFactory,
        \Vnecoms\VendorsCms\Helper\Data $cmsHelper
    ) {
        $this->cmsPage = $cmsPage;
        $this->_coreRegistry = $coreRegistry;
        $this->_request = $request;
        $this->_vendorCmsHelper = $cmsHelper;
        $this->_cmsPageFactory = $pageFactory;
    }


    /**
     * @param EventObserver $observer
     *
     * @return bool|null
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->_vendorCmsHelper->isEnabled()) {
            return;
        }

        $condition = $observer->getCondition();
        $requestPath = $condition->getRequestPath();
        $vendor = $this->getVendor();
        if (!$vendor) return;
        $pageId = $this->cmsPage->checkIdentifier($requestPath, $vendor->getId());

        if ($pageId) {
            $this->_request->setModuleName('vendorscms')->setControllerName('page')
                ->setActionName('view')
                ->setParam('page_id', $pageId);
            $this->_request->setAlias(\Magento\Framework\Url::REWRITE_REQUEST_PATH_ALIAS, $requestPath);
            if (!$this->_coreRegistry->registry('vendor_cms_page')) {
                $this->_coreRegistry->register('vendor_cms_page', true);
            }

            return;
        }
    }

    protected function getVendor()
    {
        return $this->_coreRegistry->registry('vendor');
    }
}
