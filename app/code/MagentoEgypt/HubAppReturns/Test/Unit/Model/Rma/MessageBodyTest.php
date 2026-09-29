<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Test\Unit\Model\Rma;

use MagentoEgypt\HubAppReturns\Model\Rma\MessageBody;
use PHPUnit\Framework\TestCase;

class MessageBodyTest extends TestCase
{
    public function testPlainTextIsStoredEscapedWithLineBreaks(): void
    {
        self::assertSame(
            '<p>a &lt;b&gt; &amp; &quot;c&quot;<br>' . "\n" . 'd</p>',
            MessageBody::fromPlainText("  a <b> & \"c\"\r\nd  ")
        );
    }

    public function testScriptsHandlersAndUnsafeLinksAreRemoved(): void
    {
        $html = MessageBody::toHtml(
            '<p>Hi <script>alert(1)</script><b onclick="x()">bold</b> '
            . '<a href="javascript:alert(1)">bad</a> <a href="https://hub.test/a" target="_blank">ok</a></p>'
            . '<img src=x onerror=alert(1)><style>p{}</style><!-- note --><iframe src="//evil"></iframe>'
        );

        self::assertSame('<p>Hi <b>bold</b> <a>bad</a> <a href="https://hub.test/a">ok</a></p>', $html);
    }

    public function testUnknownElementsAreUnwrappedAndArabicSurvives(): void
    {
        self::assertSame(
            '<div>المنتج <i>معطوب</i></div>',
            MessageBody::toHtml('<div><font color="red">المنتج <i>معطوب</i></font></div>')
        );
    }

    public function testPlainStoredMessageKeepsItsLines(): void
    {
        self::assertSame("first\n\nsecond & third", MessageBody::toText("first\n\nsecond &amp; third"));
        self::assertStringContainsString('<br>', MessageBody::toHtml("first\nsecond"));
    }

    public function testTextOfHtml(): void
    {
        self::assertSame(
            "Hello bold\n- one\n- two",
            MessageBody::toText('<p>Hello&nbsp;<b>bold</b></p><ul><li>one</li><li>two</li></ul>')
        );
    }

    public function testEmpty(): void
    {
        self::assertSame('', MessageBody::toHtml('  '));
        self::assertSame('', MessageBody::toText(''));
    }
}
