<?php

namespace Vnecoms\VendorsCms\Controller\Vendors\Block;

use Vnecoms\Vendors\Controller\Vendors\Action;

/**
 * Created by PhpStorm.
 * User: mrtuvn
 * Date: 19/12/2016
 * Time: 17:04.
 */
class Index extends Action
{
    /** @var \Magento\Framework\Registry  */
    protected $_coreRegistry;

    /**
     * Authorization level of a basic admin session.
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsCms::block';

    /**
     * Index constructor.
     *
     * @param \Vnecoms\Vendors\App\Action\Context $context
     */
    public function __construct(
        \Vnecoms\Vendors\App\Action\Context $context
    ) {
        parent::__construct($context);
        $this->_coreRegistry = $context->getCoreRegsitry();
    }

    /**
     */
    public function execute()
    {
        $this->_initAction();
        $this->setActiveMenu('Vnecoms_VendorsCms::block');
        //$this->getRequest()->setParam('vendor_id',$this->_session->getVendor()->getId());
        $title = $this->_view->getPage()->getConfig()->getTitle();
        $title->prepend(__('Vendor CMS'));
        $title->prepend(__('Blocks'));
        $this->_view->renderLayout();
    }
}
