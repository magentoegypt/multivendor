<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Delivery Date for Magento 2 (System)
 */

namespace Amasty\CheckoutDeliveryDate\Block\Sales\Order\Email;

class Delivery extends \Amasty\CheckoutDeliveryDate\Block\Sales\Order\Info\Delivery
{
    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _construct()
    {
        parent::_construct();

        $this->setTemplate('Amasty_CheckoutDeliveryDate::sales/order/email/delivery.phtml')
            ->setData('area', 'frontend');
    }
}
