<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Delivery Date for Magento 2 (System)
 */

namespace Amasty\CheckoutDeliveryDate\Observer\QuoteSubmit;

use Amasty\CheckoutDeliveryDate\Api\Data\DeliveryInterface;
use Amasty\CheckoutDeliveryDate\Api\DeliveryInformationManagementInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SetDeliveryData implements ObserverInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var DeliveryInformationManagementInterface
     */
    private $deliveryInformationManagement;

    public function __construct(
        RequestInterface $request,
        DeliveryInformationManagementInterface $deliveryInformationManagement
    ) {
        $this->request = $request;
        $this->deliveryInformationManagement = $deliveryInformationManagement;
    }

    /**
     * 'sales_model_service_quote_submit_before' event
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getQuote();
        $deliveryInfo = $this->request->getParam('am_checkout_deliverydate');

        if (!empty($deliveryInfo) && is_array($deliveryInfo)) {
            $deliveryInfoDefault = [
                DeliveryInterface::DATE => null,
                DeliveryInterface::TIME => null,
                DeliveryInterface::COMMENT => null
            ];
            $deliveryInfo += $deliveryInfoDefault;
            $this->deliveryInformationManagement->update(
                (int)$quote->getId(),
                (string)$deliveryInfo[DeliveryInterface::DATE],
                $deliveryInfo[DeliveryInterface::TIME],
                $deliveryInfo[DeliveryInterface::COMMENT]
            );
        }
    }
}
