<?php

namespace Vnecoms\Quotation\Block\Quotepage;

use Magento\Framework\View\Element\Template;
use Vnecoms\Quotation\Model\Source\FormConfig;
use Vnecoms\Quotation\Model\QuoteConfigProvider;

class Sidebar extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\Data\Form\FormKey
     */
    protected $formKey;

    /**
     * @var bool
     */
    protected $_isScopePrivate = false;
    
    /**
     * @var \Vnecoms\Quotation\Helper\Data
     */
    protected $helper;

    /**
     * @var QuoteConfigProvider
     */
    protected $configProvider;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    
    /**
     * @param Template\Context $context
     * @param \Magento\Framework\Data\Form\FormKey $formKey
     * @param \Vnecoms\Quotation\Helper\Data $helper
     * @param QuoteConfigProvider $configProvider
     * @param \Magento\Customer\Model\Session $customerSession
     * @param array $data
     */
    public function __construct
    (
        Template\Context $context,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Vnecoms\Quotation\Helper\Data $helper,
        QuoteConfigProvider $configProvider,
        \Magento\Customer\Model\Session $customerSession,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->formKey = $formKey;
        $this->helper = $helper;
        $this->configProvider = $configProvider;
        $this->customerSession = $customerSession;
        $this->_isScopePrivate = true;
    }

    /**
     * Retrieve form key
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }


    public function getQuotationConfig()
    {
        return $this->configProvider->getConfig();
    }

    /**
     * Get base url for block.
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getBaseUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }
    
    /**
     * Is login required
     * 
     * @return boolean
     */
    public function showLoginRequired(){
        return $this->helper->isLoginRequired() && !$this->customerSession->isLoggedIn();
    }
    
    /**
     * Is Enabled Telephone
     * @return boolean
     */
    public function isEnabledTelephone(){
        return $this->helper->getTelephoneConfig() > 0;
    }
    
    /**
     * Is required telephone
     * 
     * @return boolean
     */
    public function isRequiredTelephone(){
        return $this->helper->getTelephoneConfig() == FormConfig::CONFIG_YES_REQUIRED;
    }
    
    /**
     * Is Enabled Company
     * @return boolean
     */
    public function isEnabledCompany(){
        return $this->helper->getCompanyConfig() > 0;
    }
    
    /**
     * Is required Company
     *
     * @return boolean
     */
    public function isRequiredCompany(){
        return $this->helper->getCompanyConfig() == FormConfig::CONFIG_YES_REQUIRED;
    }
    
    /**
     * Is Enabled tax
     * @return boolean
     */
    public function isEnabledTaxId(){
        return $this->helper->getTaxIdConfig() > 0;
    }
    
    /**
     * Is required tax
     *
     * @return boolean
     */
    public function isRequiredTaxId(){
        return $this->helper->getTaxIdConfig() == FormConfig::CONFIG_YES_REQUIRED;
    }
    
    /**
     * Get customer object
     * 
     * @return \Magento\Customer\Model\Customer
     */
    public function getCustomer(){
        return $this->customerSession->getCustomer();
    }
}