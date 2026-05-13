<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 */

namespace Vnecoms\VendorsCms\Controller\Vendors\Block\App;

use Vnecoms\Vendors\App\Action\Context;
use Magento\Framework\View\LayoutFactory;
use Magento\Framework\Controller\Result\RawFactory;

class Grid extends \Vnecoms\Vendors\Controller\Vendors\Action
{
    /**
     * Authorization level of a basic admin session.
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsCms::app';
    /**
     * @var \Magento\Framework\View\LayoutFactory
     */
    protected $layoutFactory;

    /**
     * @var RawFactory
     */
    protected $resultRawFactory;

    /**
     * @param Context       $context
     * @param LayoutFactory $layoutFactory
     * @param RawFactory    $resultRawFactory
     */
    public function __construct(Context $context, LayoutFactory $layoutFactory, RawFactory $resultRawFactory)
    {
        $this->layoutFactory = $layoutFactory;
        $this->resultRawFactory = $resultRawFactory;
        parent::__construct($context);
    }

    /**
     * Chooser Source action.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $this->_view->loadLayout();
        return $this->getResponse()->setBody($this->_view->getLayout()->getBlock('block.app.grid')->toHtml());

    }
}
