<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\Credit\Block\Adminhtml\Customer\Edit\Tab;

use Magento\Customer\Controller\RegistryConstants;
use Magento\Ui\Component\Layout\Tabs\TabInterface;

/**
 * Customer account form block
 */
class Credit extends \Magento\Backend\Block\Template implements TabInterface
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Vnecoms\Credit\Model\Credit
     */
    protected $_creditAccount;

    /**
     * @var \Vnecoms\Credit\Model\CreditFactory
     */
    protected $_creditFactory;

    /**
     * @var \Magento\Authorization\Model\Acl\AclRetriever
     */
    protected $aclRetriever;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $authSession;

    /**
     * Credit constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Vnecoms\Credit\Model\CreditFactory $creditFactory
     * @param \Magento\Authorization\Model\Acl\AclRetriever $aclRetriever
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Vnecoms\Credit\Model\CreditFactory $creditFactory,
        \Magento\Authorization\Model\Acl\AclRetriever $aclRetriever,
        \Magento\Backend\Model\Auth\Session $authSession,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_creditFactory = $creditFactory;
        $this->aclRetriever = $aclRetriever;
        $this->authSession = $authSession;
        parent::__construct($context, $data);
    }

    protected function _prepareLayout()
    {
        $this->addChild('transaction_grid', 'Vnecoms\Credit\Block\Adminhtml\Customer\Edit\Tab\Credit\Transaction\Grid');

        return parent::_prepareLayout();
    }

    /**
     * @return string|null
     */
    public function getCustomerId()
    {
        return $this->_coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
    }

    /**
     * Get credit account object
     * @return \Vnecoms\Credit\Model\Credit
     */
    public function getCreditAccount()
    {
        if (!$this->_creditAccount) {
            $this->_creditAccount = $this->_creditFactory->create()->loadByCustomerId($this->getCustomerId());
        }
        return $this->_creditAccount;
    }

    /**
     * Format Credit
     * @param float $credit
     */
    public function formatCredit($credit)
    {
        $baseCurrency = $this->_storeManager->getStore()->getBaseCurrency();
        return $baseCurrency->formatPrecision($credit, 2, [], false);
    }

    /**
     * Get formated credit balance
     * @return string
     */
    public function getFormatedCreditBalance()
    {
        return $this->formatCredit($this->getCreditAccount()->getCredit());
    }

    /**
     * Get add credit URL
     * @return string
     */
    public function getAddCreditUrl()
    {
        return $this->getUrl("vstorecredit/transaction/save");
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Store Credit (%1)', $this->getFormatedCreditBalance());
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Store Credit (%1)', $this->getFormatedCreditBalance());
    }

    /**
     * @return bool
     */
    public function canShowTab()
    {
        if ($this->getCustomerId() && $this->getIsAllowed("Vnecoms_Credit::credit")) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isHidden()
    {
        if ($this->getCustomerId() && $this->getIsAllowed("Vnecoms_Credit::credit")) {
            return false;
        }
        return true;
    }

    /**
     * Tab class getter
     *
     * @return string
     */
    public function getTabClass()
    {
        return '';
    }

    /**
     * Return URL link to Tab content
     *
     * @return string
     */
    public function getTabUrl()
    {
        return '';
    }

    /**
     * Tab should be loaded trough Ajax call
     *
     * @return bool
     */
    public function isAjaxLoaded()
    {
        return false;
    }

    /**
     * @param $resource
     * @return bool
     */
    protected function getIsAllowed($resource) { // like a string, e.g. "Magento_Backend::cache" for Cache Management
        $role = $this->authSession->getUser()->getRole();
        $resources = $this->aclRetriever->getAllowedResourcesByRole($role->getId());
        return in_array($resource, $resources) or in_array("Magento_Backend::all", $resources);
    }
}
