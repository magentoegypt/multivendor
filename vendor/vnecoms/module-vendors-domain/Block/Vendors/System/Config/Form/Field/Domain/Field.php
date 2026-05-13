<?php
namespace Vnecoms\VendorsDomain\Block\Vendors\System\Config\Form\Field\Domain;

use Vnecoms\VendorsDomain\Model\Domain as VendorDomain;

class Field extends \Magento\Framework\View\Element\Template
{
    protected $_template = 'Vnecoms_VendorsDomain::system/config/field/domain.phtml';
    
    /**
     * @var \Vnecoms\VendorsPage\Helper\Data
     */
    protected $pageHelper;
    
    /**
     * @var \Vnecoms\VendorsDomain\Helper\Data
     */
    protected $domainHelper;
    
    /**
     * @var \Vnecoms\Vendors\Model\Session
     */
    protected $vendorSession;
    
    /**
     * @var \Vnecoms\VendorsDomain\Model\DomainFactory
     */
    protected $domainFactory;
    
    /**
     * @var \Vnecoms\VendorsDomain\Model\Source\Status
     */
    protected $domainStatus;
    
    /**
     * 
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Vnecoms\VendorsPage\Helper\Data $pageHelper
     * @param \Vnecoms\VendorsDomain\Helper\Data $domainHelper
     * @param \Vnecoms\Vendors\Model\Session $vendorSession
     * @param \Vnecoms\VendorsDomain\Model\DomainFactory $domainFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Vnecoms\VendorsPage\Helper\Data $pageHelper,
        \Vnecoms\VendorsDomain\Helper\Data $domainHelper,
        \Vnecoms\Vendors\Model\Session $vendorSession,
        \Vnecoms\VendorsDomain\Model\DomainFactory $domainFactory,
        \Vnecoms\VendorsDomain\Model\Source\Status $domainStatus,
        array $data = []
    ) {
        $this->vendorSession = $vendorSession;
        $this->pageHelper = $pageHelper;
        $this->domainHelper = $domainHelper;
        $this->domainFactory = $domainFactory;
        $this->domainStatus = $domainStatus;
        parent::__construct($context, $data);
        $this->initData();
    }

    /**
     * Init data
     * 
     * @return \Vnecoms\VendorsDomain\Block\Vendors\System\Config\Form\Field\Domain\Field
     */
    public function initData(){
        $transport = new \Magento\Framework\DataObject([]);
        $this->_eventManager->dispatch(
            'vnecoms_vendorsdomain_field_prepare',
            ['transport' => $transport, 'vendor' => $this->getVendor()]
        );
        $data = $transport->getData();

        foreach($data as $key => $value){
            $this->setData($key, $value);
        }
        
        return $this;
    }
    
    /**
     * Get Vendor object
     *
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor(){
        return $this->vendorSession->getVendor();
    }
    
    /**
     * Get Homepage URL
     *
     * @return string
     */
    public function getHomeUrl(){
        return $this->pageHelper->getUrl($this->getVendor(),'');
    }
    
    /**
     * Get main domain
     * 
     * @return string
     */
    public function getMainDomain(){
        return $this->domainHelper->getMainDomain();
    }
    
    /**
     * @return \Vnecoms\VendorsDomain\Model\Domain
     */
    public function getDomainObject(){
        if(!$this->getData('domain_obj')){
            $domain = $this->domainFactory->create()->load($this->getVendor()->getId(),'vendor_id');
            $this->setData('domain_obj', $domain);
        }
        
        return $this->getData('domain_obj');
    }
    
    /**
     * Get Status Label
     * 
     * @param \Vnecoms\VendorsDomain\Model\Domain $domainObj
     * @return string
     */
    public function getStatusLabel(\Vnecoms\VendorsDomain\Model\Domain $domainObj){
        $status = $this->domainStatus->getOptionArray();
        return isset($status[$domainObj->getStatus()])?$status[$domainObj->getStatus()]:__("Disabled");
    }
    
    /**
     * Get Status CSS Class
     * 
     * @param \Vnecoms\VendorsDomain\Model\Domain $domainObj
     * @return string
     */
    public function getStatusClass(\Vnecoms\VendorsDomain\Model\Domain $domainObj){
        switch($domainObj->getStatus()){
            case VendorDomain::STATUS_PENDING:
                return 'bg-yellow';
            case VendorDomain::STATUS_APPROVED:
                return 'bg-green';
        }
        return 'bg-red';
    }
    
    /**
     * Get DNS Instruction
     * 
     * @return string
     */
    public function getDnsInstruction(){
        return $this->domainHelper->getDnsInstruction();
    }
    
    /**
     * Can use domain
     * 
     * @return boolean
     */
    public function canUseDomain(){
        if($this->getData('can_use_domain') === null){
            $this->setData('can_use_domain', true);
        }
        
        return (bool)$this->getData('can_use_domain');
    }
    
    /**
     * Can use subdomain
     *
     * @return boolean
     */
    public function canUseSubDomain(){
        if($this->getData('can_use_subdomain') === null){
            $this->setData('can_use_subdomain', true);
        }
    
        return (bool)$this->getData('can_use_subdomain');
    }
    
    /**
     * Can change subdomain
     * 
     * @return boolean
     */
    public function canChangeSubdomain(){
        if($this->getData('can_change_subdomain') === null){
            $this->setData('can_change_subdomain', false);
        }
        
        return (bool)$this->getData('can_change_subdomain');
    }
}
