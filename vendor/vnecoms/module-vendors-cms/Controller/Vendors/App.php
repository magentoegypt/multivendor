<?php


/**
 * Adminhtml Manage Widgets Instance Controller.
 */

namespace Vnecoms\VendorsCms\Controller\Vendors;

abstract class App extends \Vnecoms\Vendors\Controller\Vendors\Action
{
    /**
     * Authorization level of a basic admin session.
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsCms::app';


    /**
     * @var \Vnecoms\VendorsCms\Model\AppFactory
     */
    protected $_appFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Magento\Framework\Math\Random
     */
    protected $mathRandom;

    /**
     * @var \Magento\Framework\Translate\InlineInterface
     */
    protected $_translateInline;

    /**
     * @var \Vnecoms\Vendors\Model\Session
     */
    protected $vendorSession;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param \Vnecoms\Vendors\App\Action\Context          $context
     * @param \Vnecoms\VendorsCms\Model\AppFactory         $appFactory
     * @param \Psr\Log\LoggerInterface                     $logger
     * @param \Magento\Framework\Math\Random               $mathRandom
     * @param \Magento\Framework\Translate\InlineInterface $translateInline
     */
    public function __construct(
        \Vnecoms\Vendors\App\Action\Context $context,
        \Vnecoms\VendorsCms\Model\AppFactory $appFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Math\Random $mathRandom,
        \Magento\Framework\Translate\InlineInterface $translateInline,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->_translateInline = $translateInline;
        $this->_appFactory = $appFactory;
        $this->_logger = $logger;
        $this->mathRandom = $mathRandom;
        $this->scopeConfig = $scopeConfig;

        parent::__construct($context);
    }

    /**
     * Load layout, set active menu and breadcrumbs.
     *
     * @return $this
     */
    protected function _initAction()
    {
        $this->_view->loadLayout();
        $this->_setActiveMenu(
            'Vnecoms_VendorsCms::app'
        )->_addBreadcrumb(
            __('CMS'),
            __('CMS')
        )->_addBreadcrumb(
            __('Manage Frontend Applications'),
            __('Manage Frontend Applications')
        );

        return $this;
    }

    /**
     * Init widget instance object and set it to registry.
     *
     * @return \Vnecoms\VendorsCms\Model\App|bool
     */
    protected function _initApp()
    {
        /** @var $app \Vnecoms\VendorsCms\Model\App */
        $app = $this->_appFactory->create();

        $code = $this->getRequest()->getParam('code', null);
        $appId = $this->getRequest()->getParam('app_id', null);
        if ($appId) {
            if ($app->load($appId)->getVendorId() != $this->getVendor()->getId()) {
                $resultRedirect = $this->resultRedirectFactory->create();
                $this->messageManager->addError(__('You can\'t access this App.'));
                return false;
            }

            $app->load($appId)->setCode($code);
            if (!$app->getId()) {
                $this->messageManager->addError(__('Please specify a correct application.'));

                return false;
            }
        } else {
            // Widget id was not provided on the query-string.  Locate the widget instance
            // type (namespace\classname) based upon the widget code (aka, widget id).
            $type = $code != null ? $app->getReference('code', $code, 'type') : null;
            $app->setType($type)->setCode($code)->setInstanceType($code);
            $app->setThemeId($this->getCurrentThemeId());
        }
        $this->_coreRegistry->register('current_app', $app);

        return $app;
    }

    /**
     * Set body to response.
     *
     * @param string $body
     */
    protected function setBody($body)
    {
        $this->_translateInline->processResponseBody($body);

        $this->getResponse()->setBody($body);
    }

    /**
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor()
    {
        if (!$this->vendorSession) {
            $this->vendorSession = $this->_objectManager->get('Vnecoms\Vendors\Model\Session');
        }

        return $this->vendorSession->getVendor();
    }

    /**
     * get Current  Theme of this vendor.
     *
     * @todo
     */
    protected function getCurrentThemeId()
    {
        $customer = $this->getVendor()->getCustomer();
        $storeId = $customer->getStoreId();

        $themeId = $this->scopeConfig->getValue(
            \Magento\Framework\View\DesignInterface::XML_PATH_THEME_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $themeId;
    }
}
