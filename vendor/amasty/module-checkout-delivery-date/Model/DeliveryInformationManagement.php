<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Delivery Date for Magento 2 (System)
 */

namespace Amasty\CheckoutDeliveryDate\Model;

use Amasty\CheckoutDeliveryDate\Api\DeliveryInformationManagementInterface;
use Amasty\CheckoutDeliveryDate\Model\ResourceModel\Delivery as DeliveryResource;
use Magento\Framework\Escaper;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class DeliveryInformationManagement implements DeliveryInformationManagementInterface
{
    /**
     * @var DeliveryResource
     */
    private $deliveryResource;

    /**
     * @var DeliveryDateProvider
     */
    private $deliveryProvider;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    public function __construct(
        DeliveryResource $deliveryResource,
        DeliveryDateProvider $deliveryProvider,
        TimezoneInterface $timezone,
        Escaper $escaper
    ) {
        $this->deliveryResource = $deliveryResource;
        $this->deliveryProvider = $deliveryProvider;
        $this->timezone = $timezone;
        $this->escaper = $escaper;
    }

    /**
     * @param int $cartId
     * @param string $date
     * @param int $time
     * @param string $comment
     * @return bool
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function update($cartId, $date, $time = -1, $comment = ''): bool
    {
        $delivery = $this->deliveryProvider->findByQuoteId((int)$cartId);
        $date = $this->formatDate($date);
        $delivery->addData([
            'date' => $date ?: null,
            'time' => $time >= 0 ? $time : null,
            'comment' => ($comment) ? $this->escaper->escapeHtml($comment) : null
        ]);

        if ($delivery->getData('date') === null
            && $delivery->getData('time') === null
            && $delivery->getData('comment') === null
        ) {
            if ($delivery->getId()) {
                $this->deliveryResource->delete($delivery);
            }
        } else {
            $this->deliveryResource->save($delivery);
        }

        return true;
    }

    private function formatDate(string $date): string
    {
        // M - in format - is textual representation of a month but in $date we have numeric representation
        // For correct converting string $date into object DateTime, we need 'M' replace by 'm'
        $format = str_replace('M', 'm', $this->getDateFormat());
        $date = date_create_from_format($format, $date);

        return $date ? (string)$date->getTimestamp() : '';
    }

    public function getDateFormat(): string
    {
        return $this->timezone->getDateFormat(\IntlDateFormatter::SHORT);
    }
}
