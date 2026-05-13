<?php
namespace Vnecoms\VendorsCustomTheme\Controller\Adminhtml;

class Action extends \Magento\Backend\App\Action
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;
    
    /**
     * Is access to section allowed
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return parent::_isAllowed() && $this->_authorization->isAllowed('Vnecoms_VendorsCustomTheme::theme');
    }
    
    /**
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $registry
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context);
    }
    
    /**
     * Init action
     *
     * @return $this
     */
    protected function _initAction()
    {
        $this->_view->loadLayout();
        $this->_setActiveMenu(
            'Vnecoms_Vendors::marketplace'
        )->_addBreadcrumb(
            __('Marketplace'),
            __('Marketplace')
        );
        return $this;
    }
    
    /**
     * @return void
     */
    public function execute()
    {
        return $this;
    }
}
