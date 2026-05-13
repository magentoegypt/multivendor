<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package One Step Checkout Thank you Page 2 for Magento 2 (System)
 */

namespace Amasty\CheckoutThankYouPage\Block\Onepage\Success;

use Magento\Checkout\Model\Session;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order;

class Details extends Template
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var Session
     */
    protected $session;

    public function __construct(
        Template\Context $context,
        Registry $registry,
        Session $session,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->session = $session;
    }

    protected function _prepareLayout()
    {
        if (!$this->registry->registry('current_order')) {
            $this->registry->register('current_order', $this->getOrder());
        }

        return parent::_prepareLayout();
    }

    /**
     * Retrieve current order model instance
     *
     * @return Order
     */
    public function getOrder(): Order
    {
        return $this->session->getLastRealOrder();
    }
}
