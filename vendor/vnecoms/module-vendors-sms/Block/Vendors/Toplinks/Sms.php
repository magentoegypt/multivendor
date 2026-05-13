<?php
namespace Vnecoms\VendorsSms\Block\Vendors\Toplinks;

/**
 * Vendor Notifications block
 */
class Sms extends \Vnecoms\Vendors\Block\Vendors\AbstractBlock
{
    /**
     * @var \Vnecoms\Vendors\Model\Session
     */
    protected $vendorSession;
    
    
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Vnecoms\Vendors\Model\UrlInterface $url,
        \Vnecoms\Vendors\Model\Session $vendorSession,
        array $data = [])
    {
        parent::__construct($context, $url, $data);
        $this->vendorSession = $vendorSession;
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
    public function formatCredit($credit){
        return $this->_storeManager->getStore()->getBaseCurrency()->formatPrecision($credit, 4, [], false);
    }
    
    /**
     * Get Withdraw URL
     * 
     * @return string
     */
    public function getSmsHistoryUrl(){
        return $this->getUrl('sms/manage');
    }
}
