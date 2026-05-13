<?php

namespace Vnecoms\BannerManager\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class Index
 * @category Vnecoms
 * @package  Vnecoms_BannerManager
 * @module   BannerManager
 * @author   Vnecoms Developer Team
 */
class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @var \Magento\Framework\View\Result\LayoutFactory
     */
    protected $_layoutFactory;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    public function __construct(
        Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\View\Result\LayoutFactory $layoutFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);
        $this->_resultPageFactory = $resultPageFactory;
        $this->_registry = $coreRegistry;
        $this->_layoutFactory = $layoutFactory;
        $this->_scopeConfig = $scopeConfig;

    }

    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        return $this->_resultPageFactory->create();
    }

}
