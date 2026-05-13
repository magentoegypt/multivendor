<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Delivery Date for Magento 2 (System)
 */

namespace Amasty\CheckoutDeliveryDate\Block\Sales\Order\Info;

use Amasty\CheckoutDeliveryDate\Model\DeliveryDateProvider;
use Magento\Checkout\Model\Session;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Delivery extends Template
{
    /**
     * @var DeliveryDateProvider
     */
    protected $deliveryProvider;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    public function __construct(
        Context $context,
        DeliveryDateProvider $deliveryProvider,
        Session $checkoutSession,
        TimezoneInterface $timezone,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->deliveryProvider = $deliveryProvider;
        $this->checkoutSession = $checkoutSession;
        $this->timezone = $timezone;
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('sales/order/info/delivery.phtml');
    }

    /**
     * @return bool|int
     */
    private function getCurrentOrderId()
    {
        if ($orderId = $this->getOrderId()) {
            return (int)$orderId;
        }

        if ($orderId = $this->getRequest()->getParam('order_id')) {
            return (int)$orderId;
        }

        if ($lastRealOrder = $this->checkoutSession->getLastRealOrder()) {
            if ($orderId = $lastRealOrder->getId()) {
                return (int)$orderId;
            }
        }

        return false;
    }

    /**
     * @return bool|int
     */
    private function getCurrentQuoteId()
    {
        if ($quoteId = $this->getQuoteId()) {
            return (int)$quoteId;
        }

        if ($quoteId = $this->checkoutSession->getQuoteId()) {
            return (int)$quoteId;
        }

        return false;
    }

    /**
     * @return array|bool
     */
    public function getDeliveryDateFields()
    {
        if ($orderId = $this->getCurrentOrderId()) {
            $delivery = $this->deliveryProvider->findByOrderId($orderId);
        } elseif ($quoteId = $this->getCurrentQuoteId()) {
            $delivery = $this->deliveryProvider->findByQuoteId($quoteId);
        } else {
            return false;
        }

        if (!$delivery->getId()) {
            return false;
        }

        return $this->getDeliveryFields($delivery);
    }

    /**
     * @param \Amasty\CheckoutDeliveryDate\Model\Delivery $delivery
     *
     * @return array
     */
    public function getDeliveryFields($delivery)
    {
        $date = $delivery->getDate();
        $time = $delivery->getTime();

        $fields = [];
        if (!empty($date)) {
            $fields[] = [
                'label' => __('Delivery Date'),
                'value' => $this->timezone->formatDate($date, \IntlDateFormatter::FULL, false)
            ];
        }

        if ($time !== null && $time >= 0) {
            $fields[] = [
                'label' => __('Delivery Time'),
                'value' => $time . ':00 - ' . (($time) + 1) . ':00',
            ];
        }

        if ($delivery->getComment()) {
            $fields[] = [
                'label' => __('Delivery Comment'),
                'value' => $delivery->getComment(),
            ];
        }

        return $fields;
    }
}
