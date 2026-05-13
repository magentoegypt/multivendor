<?php
namespace Vnecoms\VendorsSms\Block\Vendors\Sms;

/**
 * Vendor Notifications block
 */
class Info extends \Vnecoms\Vendors\Block\Vendors\AbstractBlock
{
    /**
     * @var \Vnecoms\Vendors\Model\Session
     */
    protected $vendorSession;
    
    /**
     * @var \Vnecoms\Credit\Model\CreditFactory
     */
    protected $creditFactory;
    
    /**
     * @var \Vnecoms\VendorsSms\Helper\Data
     */
    protected $helper;
    
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;
    
    
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Vnecoms\Vendors\Model\UrlInterface $url
     * @param \Vnecoms\Vendors\Model\Session $vendorSession
     * @param \Vnecoms\Credit\Model\CreditFactory $creditAccountFactory
     * @param \Vnecoms\VendorsSms\Helper\Data $helper
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Vnecoms\Vendors\Model\UrlInterface $url,
        \Vnecoms\Vendors\Model\Session $vendorSession,
        \Vnecoms\Credit\Model\CreditFactory $creditAccountFactory,
        \Vnecoms\VendorsSms\Helper\Data $helper,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        array $data = []
    ) {
        parent::__construct($context, $url, $data);
        $this->vendorSession = $vendorSession;
        $this->creditFactory = $creditAccountFactory;
        $this->helper = $helper;
        $this->date = $date;
    }
    
    /**
     * Get credit
     *
     * @return number
     */
    public function getSmsCredit(){
        return $this->vendorSession->getVendor()->getSmsCredit();
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
     * Get Today message Count
     * 
     * @return number
     */
    public function getTodayMessageCount(){
        return $this->helper->countVendorSms(
            $this->vendorSession->getVendor()->getId(),
            $this->date->date('Y-m-d')
        );
    }
    
    /**
     * Get today credit deducted
     * 
     * @return string
     */
    public function getTodayCreditDeducted(){
        return $this->formatCredit(
            $this->helper->getChargedCredit(
                $this->vendorSession->getVendor()->getId(),
                $this->date->date('Y-m-d')
            )
        );
    }
    
    /**
     * Get current credit
     * 
     * @return string
     */
    public function getCurrentSmsCredit(){
        return $this->formatCredit($this->getSmsCredit());
    }
    
    /**
     * Get credit account
     *
     * @return \Vnecoms\Credit\Model\Credit
     */
    public function getCreditAccount(){
        if(!$this->getData('credit_account')){
            $creditAccount = $this->creditFactory->create();
            $creditAccount->loadByCustomerId($this->vendorSession->getCustomer()->getId());
            $this->setData('credit_account',$creditAccount);
        }
    
        return $this->getData('credit_account');
    }
    
    /**
     * Get credit
     *
     * @return number
     */
    public function getCredit(){
        return $this->getCreditAccount()->getCredit();
    }
    
    /**
     * Get Credit Balance
     * 
     * @return number
     */
    public function getCurrentCreditBalance(){
        return $this->formatCredit($this->getCredit(), 2);
    }
    
    /**
     * Get Sms Credit Packages
     * 
     * @return array
     */
    public function getSmsCreditPackages(){
        return $this->helper->getSmsCreditPackages();
    }
    
    /**
     * get SMS credit JSON
     * 
     * @return string
     */
    public function getSmsCreditJSON(){
        $data = [];
        foreach($this->getSmsCreditPackages() as $package){
            $data[] = [
                'credit'            => $package['credit'],
                'price'             => $package['price'],
                'price_formated'    => $this->formatCredit($package['price'], 2),
                'credit_formated'   => $this->formatCredit($package['credit'], 2),
            ];
        }
        
        return json_encode($data);
    }
    
    /**
     * Get buy credit URL
     * 
     * @return string
     */
    public function getBuyCreditUrl(){
        return $this->getUrl('sms/manage/buy');
    }
}
