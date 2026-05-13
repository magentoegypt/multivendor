<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Observer\Adminhtml;

use Magento\Framework\Event\ObserverInterface;

class PreDispatchAdminhtmlSystemConfigEditObserver implements ObserverInterface
{
    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $backendSession;

    /**
     * @var \Vnecoms\VendorsPage\Helper\Data
     */
    protected $vendorHelper;

    public function __construct(
        \Magento\Backend\Model\Session $session,
        \Vnecoms\VendorsPage\Helper\Data $vendorHelper
    ) {
        $this->backendSession = $session;
        $this->vendorHelper = $vendorHelper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        // TODO: Implement execute() method.
        if ($observer->getEvent()->getRequest()->getParam('section') == 'vendors') {
            $this->backendSession->setData('current_url_key', $this->vendorHelper->getUrlKey());
        }
    }
}
