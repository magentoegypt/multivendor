<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * request_before_save_message, GraphQL area only (etc/graphql/events.xml): a message the app posts on
 * a return is dated when it is posted.
 *
 * Vnecoms\RMA\Model\Request::_createMessageObject() dates every message with the RETURN's created_at,
 * so each reply the app saved (MessagePoster) showed the time the return was filed, to the customer
 * (HmReturnMessage.created_at) and in the admin and seller panels. The event hands over the message
 * data before it is saved; created_at becomes now, UTC (the database session runs in UTC, as
 * Magento sets it). Messages saved in any other area (the website's own forms and panels) keep
 * Vnecoms' behaviour.
 */
class DateAppMessage implements ObserverInterface
{
    public function __construct(
        private readonly DateTime $dateTime
    ) {
    }

    public function execute(Observer $observer): void
    {
        $message = $observer->getEvent()->getData('transport');
        if ($message instanceof DataObject) {
            $message->setData('created_at', $this->dateTime->gmtDate('Y-m-d H:i:s'));
        }
    }
}
