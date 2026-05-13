<?php
namespace Vnecoms\VendorsCredit\Block\Adminhtml\Vendor\Edit\Tab;

use Magento\Backend\Block\Widget\Form;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;

class EscrowRequests extends Generic implements TabInterface
{

    /**
     * @var string
     */
    protected $_template = 'Vnecoms_VendorsCredit::vendor/edit/tab/credit_transactions.phtml';

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Vnecoms\VendorsCredit\Helper\Data
     */
    protected $helper;

    /**
     * CreditTransactions constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Vnecoms\VendorsCredit\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Vnecoms\VendorsCredit\Helper\Data $helper,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->helper = $helper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare content for tab
     *
     * @return \Magento\Framework\Phrase
     * @codeCoverageIgnore
     */
    public function getTabLabel()
    {
        return __('Escrow Transactions');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     * @codeCoverageIgnore
     */
    public function getTabTitle()
    {
        return __('Escrow Transactions');
    }

    /**
     * Returns status flag about this tab can be showed or not
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Get current vendor object
     *
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor()
    {
        return $this->_coreRegistry->registry('current_vendor');
    }

    /**
     * Returns status flag about this tab hidden or not
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function isHidden()
    {
        $vendorGroup = $this->getVendor()->getGroupId();
        if ($this->helper->isEnableCredit() && $this->helper->isEnabledEscrowTransaction($vendorGroup)) {
            return false;
        }
        return true;
    }

    /**
     * @return Form
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        return parent::_prepareForm();
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {

        $this->setChild(
            'escrow_transactions_grid',
            $this->getLayout()->createBlock('Vnecoms\VendorsCredit\Block\Adminhtml\Vendor\Edit\Tab\Escrow\Grid', 'escrow_transactions_grid')
        );

        return parent::_prepareLayout();
    }
}
