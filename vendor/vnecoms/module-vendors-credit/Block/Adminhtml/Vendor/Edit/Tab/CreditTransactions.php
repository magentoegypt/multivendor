<?php
namespace Vnecoms\VendorsCredit\Block\Adminhtml\Vendor\Edit\Tab;

use Magento\Backend\Block\Widget\Form;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;

class CreditTransactions extends Generic implements TabInterface
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
        return __('Credit Transactions');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     * @codeCoverageIgnore
     */
    public function getTabTitle()
    {
        return __('Credit Transactions');
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
     * Returns status flag about this tab hidden or not
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function isHidden()
    {
        if ($this->helper->isEnableCredit()) {
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
            'credit_transactions_grid',
            $this->getLayout()->createBlock('Vnecoms\VendorsCredit\Block\Adminhtml\Vendor\Edit\Tab\Transaction\Grid', 'credit_transactions_grid')
        );
    
        return parent::_prepareLayout();
    }
}
