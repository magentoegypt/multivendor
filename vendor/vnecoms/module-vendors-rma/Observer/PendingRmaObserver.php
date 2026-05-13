<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsRMA\Observer;

use Magento\Framework\Event\ObserverInterface;
use Vnecoms\RMA\Model\ResourceModel\Request\CollectionFactory;
use Vnecoms\VendorsRMA\Model\Request;

/**
 * AdminNotification observer
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class PendingRmaObserver implements ObserverInterface
{
    /**
     * Url Builder
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * Vendor collection
     * @var \Vnecoms\RMA\Model\ResourceModel\Request\CollectionFactory;
     */
    protected $_requestCollection;

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
        $this->_urlBuilder = $context->getUrlBuilder();
        $this->_requestCollection = $collectionFactory->create();
        $this->_requestCollection->addAttributeToFilter('state', ['in' => [
            Request::STATE_AWAITING,
            Request::STATE_BEING
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
        return $this->_urlBuilder->getUrl($route, $params);
    }

    /**
     * Get number of pending vendor
     * @return number
     */
    public function getNumberOfPendingProduct()
    {
        return $this->_requestCollection->count();
    }

    /**
     * Add the notification if there are any vendor awaiting for approval.
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $productCount    = $this->getNumberOfPendingProduct();
        if ($productCount <= 0) {
            return;
        }

        $transport      = $observer->getTransport();
        $notifications  = $transport->getNotifications();
        $om             = \Magento\Framework\App\ObjectManager::getInstance();
        $notification   = $om->create('Magento\Framework\DataObject');

        if ($productCount ==1) {
            $notification->setData([
                'title'=> __("RMA Request"),
                'description' => __("There is a request RMA awaiting for your process.<br /><a href=\"%1\">Click here</a> to review the request.", $this->getUrl('vrma/request/open'))
            ]);
        } else {
            $notification->setData([
                'title'=> __("RMA Request"),
                'description' => __('There are <strong style="color: #ef672f">%1</strong> requests RMA awaiting for your process.<br /><a href="%2">Click here</a> to review the requests.', $productCount, $this->getUrl('vrma/request/open'))
            ]);
        }
        $notifications[] = $notification;
        $transport->setNotifications($notifications);
    }
}
