<?php
namespace Vnecoms\VendorsSms\Block\Adminhtml\Vendor\Edit\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;

class Sms extends Generic implements TabInterface
{
    /**
     * @var \Vnecoms\VendorsSms\Helper\Data
     */
    protected $helper;
    
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;
    
    
    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Vnecoms\VendorsSms\Helper\Data $helper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Vnecoms\VendorsSms\Helper\Data $helper,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->helper = $helper;
        $this->date = $date;
    }
    
    /**
     * @var string
     */
    protected $_template = 'Vnecoms_VendorsSms::vendor/edit/tab/sms.phtml';

    /**
     * Prepare content for tab
     *
     * @return \Magento\Framework\Phrase
     * @codeCoverageIgnore
     */
    public function getTabLabel()
    {
        return __('SMS Credit');
    }
    
    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     * @codeCoverageIgnore
     */
    public function getTabTitle()
    {
        return __('SMS Credit');
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
        return false;
    }
    
    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        $this->setChild(
            'sms_transaction_grid',
            $this->getLayout()->createBlock('Vnecoms\VendorsSms\Block\Adminhtml\Vendor\Edit\Tab\Sms\Grid', 'transaction_grid')
        );
    
        return parent::_prepareLayout();
    }
    
    /**
     * Get current vendor
     * 
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor(){
        return $this->_coreRegistry->registry('current_vendor');
    }
    
    /**
     * Format credit in base currency
     * @param number $credit
     * @return string
     */
    public function formatCredit($credit, $precision = 4){
        return $this->_storeManager->getStore()->getBaseCurrency()->formatPrecision($credit, $precision, [], false);
    }
    
    /**
     * Get Sms Credit
     * 
     * @return number
     */
    public function getAvailableSmsCreditAmount(){
        return $this->getVendor()->getSmsCredit();
    }
    
    /**
     * Get Total SMS
     * 
     * @return number
     */
    public function getTotalSms(){
        $vendor = $this->getVendor();
        $date = $this->date->date('Y-m-d H:i:s', $vendor->getCreatedAt());
        return $this->helper->countVendorSms($vendor->getId(), $date);
    }
    
    /**
     * Get Total Spent SMS Credit
     * 
     * @return number
     */
    public function getTotalSpentSmsCredit(){
        $vendor = $this->getVendor();
        $date = $this->date->date('Y-m-d H:i:s', $vendor->getCreatedAt());
        return $this->helper->getChargedCredit($vendor->getId(), $date);
    }
    
    /**
     * Add /Substract Sms Credit
     * 
     * @return string
     */
    public function getAddSmsCreditUrl(){
        return $this->getUrl('vendors/sms_transaction/add');
    }
}
