<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Vnecoms\VendorsPageBuilder\Controller\Vendors\Template;

use Vnecoms\Vendors\App\Action\Context;
use Vnecoms\Vendors\Controller\Vendors\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\PageBuilder\Model\Config;
use Magento\Framework\Controller\Result\ForwardFactory;

/**
 * Display template grid
 */
class Index extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session.
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsPageBuilder::template';

    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ForwardFactory
     */
    private $forwardFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Config $config
     * @param ForwardFactory $forwardFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Config $config,
        ForwardFactory $forwardFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->config = $config;
        $this->forwardFactory = $forwardFactory;
    }

    /**
     * Load the Manage Templates page
     *
     * @return \Magento\Framework\Controller\AbstractResult
     */
    public function execute()
    {
        if (!$this->config->isEnabled()) {
            return $this->forwardFactory->create()->forward('noroute');
        }

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $this->_initAction();
        $this->setActiveMenu('Vnecoms_VendorsCms::template');
        $title = $this->_view->getPage()->getConfig()->getTitle();
        $title->prepend(__('Vendor CMS'));
        $title->prepend(__('Templates'));

        $this->_view->renderLayout();
    }
}
