<?php

namespace Vnecoms\VendorsDomain\Observer;

use Magento\Framework\Event\ObserverInterface;
use Vnecoms\VendorsDomain\Model\ResourceModel\Domain\CollectionFactory;
use Vnecoms\VendorsDomain\Model\Domain;

class PendingDomainObserver implements ObserverInterface
{
    /**
     * Url Builder
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;
    
    /**
     * Vendor Domain Collection
     * @var \Vnecoms\VendorsDomain\Model\ResourceModel\Domain\Collection
     */
    protected $domainCollection;
    
    /**
     * Constructor
     *
     * @param Context $context
     * @param Registry $coreRegistry
     * @param Date $dateFilter
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        \Magento\Framework\View\Element\Context $context, 
        array $data = []
    ) {
        $this->urlBuilder = $context->getUrlBuilder();
        $this->domainCollection = $collectionFactory->create();
        $this->domainCollection->addFieldToFilter('status',['in' => [
            Domain::STATUS_PENDING,
        ]]);
    }
    
    /**
     * Generate url by route and parameters
     *
     * @param   string $route
     * @param   array $params
     * @return  string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->urlBuilder->getUrl($route, $params);
    }
    
    /**
     * Get number of pending vendor
     * @return number
     */
    public function getNumberOfPendingDomain(){
        return $this->domainCollection->count();
    }
    
    /**
     * Add the notification if there are any vendor domains awaiting for approval. 
     * 
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $domainCount    = $this->getNumberOfPendingDomain();
        if($domainCount <= 0) return;
        
        $transport      = $observer->getTransport();
        $notifications  = $transport->getNotifications();
        $om             = \Magento\Framework\App\ObjectManager::getInstance();
        $notification   = $om->create('Magento\Framework\DataObject');
        
        if($domainCount == 1){
            $notification->setData([
                'title'=> __("Vendor Domain Approval"),
                'description' => __("There is a vendor domain awaiting for your approval.<br /><a href=\"%1\">Click here</a> to review the domain.",$this->getUrl('vendors/domain/pending'))
            ]);
        }else{
            $notification->setData([
                'title'=> __("Vendor Domain Approval"),
                'description' => __('There are <strong style="color: #ef672f">%1</strong> vendor domains awaiting for your approval.<br /><a href="%2">Click here</a> to review the domains.',$domainCount,$this->getUrl('vendors/domain/pending'))
            ]);
        }
        $notifications[] = $notification;
        $transport->setNotifications($notifications);
    }
    
    
}
