<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\Quotation\Observer;

use Magento\Framework\Event\ObserverInterface;

class UnsetAllObserver implements ObserverInterface
{
    /**
     * @var \Vnecoms\Quotation\Model\Session
     */
    protected $session;

    /**
     * @param \Vnecoms\Quotation\Model\Session $session
     * @codeCoverageIgnore
     */
    public function __construct(\Vnecoms\Quotation\Model\Session $session)
    {
        $this->session = $session;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @codeCoverageIgnore
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->session->clearQuote()->clearStorage();
    }
}
