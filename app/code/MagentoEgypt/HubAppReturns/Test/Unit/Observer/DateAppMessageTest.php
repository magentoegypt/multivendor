<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Test\Unit\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Stdlib\DateTime\DateTime;
use MagentoEgypt\HubAppReturns\Observer\DateAppMessage;
use PHPUnit\Framework\TestCase;

/**
 * request_before_save_message in the GraphQL area: the app's message is dated now, not with the
 * return's filing time that Vnecoms\RMA\Model\Request::_createMessageObject() puts on every message.
 */
class DateAppMessageTest extends TestCase
{
    private const NOW_UTC = '2026-09-30 08:15:42';

    private function observer(): DateAppMessage
    {
        $clock = new class () extends DateTime {
            public function __construct()
            {
            }

            public function gmtDate($format = null, $input = null)
            {
                return $format === 'Y-m-d H:i:s' ? '2026-09-30 08:15:42' : 'unexpected format';
            }
        };

        return new DateAppMessage($clock);
    }

    public function testTheMessageIsDatedNowInsteadOfWhenTheReturnWasFiled(): void
    {
        //  What _createMessageObject() hands over for a reply on a return filed three days earlier.
        $message = new DataObject([
            'message' => '<p>Any news?</p>',
            'created_at' => '2026-09-27 10:00:00',
            'updated_at' => null,
            'type' => 'CUSTOMER REPLY',
            'request_id' => 12,
        ]);

        $this->observer()->execute(new Observer(['event' => new Event(['transport' => $message])]));

        self::assertSame(self::NOW_UTC, $message->getData('created_at'));
        self::assertSame('<p>Any news?</p>', $message->getData('message'));
        self::assertSame(12, $message->getData('request_id'));
    }

    public function testAnEventWithoutMessageDataIsLeftAlone(): void
    {
        $event = new Event(['request' => new DataObject(['created_at' => '2026-09-27 10:00:00'])]);

        $this->observer()->execute(new Observer(['event' => $event]));

        self::assertNull($event->getData('transport'));
        self::assertSame('2026-09-27 10:00:00', $event->getData('request')->getData('created_at'));
    }

    public function testOnlyTheGraphQlAreaListens(): void
    {
        //  The website keeps Vnecoms' behaviour: no global or other-area events.xml declares the observer.
        $etc = dirname(__DIR__, 3) . '/etc';
        self::assertStringContainsString(
            'MagentoEgypt\HubAppReturns\Observer\DateAppMessage',
            (string) file_get_contents($etc . '/graphql/events.xml')
        );
        foreach (['/events.xml', '/frontend/events.xml', '/adminhtml/events.xml', '/vendors/events.xml'] as $file) {
            self::assertFalse(is_file($etc . $file), $file . ' must not exist');
        }
    }
}
