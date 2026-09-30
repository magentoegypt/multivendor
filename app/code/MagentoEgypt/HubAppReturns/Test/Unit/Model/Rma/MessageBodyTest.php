<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Test\Unit\Model\Rma;

use MagentoEgypt\HubAppReturns\Model\Rma\MessageBody;
use PHPUnit\Framework\Attributes\DataProvider;
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

    /**
     * Texts the app may send as a return's first message or a reply.
     *
     * @return array<string, array{string}>
     */
    public static function hostileTexts(): array
    {
        return [
            'script' => ['<script>alert(document.cookie)</script>'],
            'image handler' => ['<img src=x onerror=alert(1)>'],
            'attribute break-out' => ['"><svg onload=alert(1)>'],
            'single quotes' => ["' onfocus='alert(1)' autofocus='"],
            'entity-encoded tag' => ['&lt;script&gt;alert(1)&lt;/script&gt;'],
            'CDATA' => ['<![CDATA[<img src=x onerror=alert(1)>]]>'],
            'comment' => ['<!--<script>alert(1)</script>-->'],
            'lines' => ["line one\r\n<b>line two</b>\nline three"],
        ];
    }

    #[DataProvider('hostileTexts')]
    public function testStoredMessageCarriesNoMarkupOfItsOwn(string $text): void
    {
        //  The admin and seller panels print a stored message unescaped (request/message/list.phtml). The
        //  only tags in what the app stores are its own <p> wrapper and <br> line breaks; everything the
        //  customer typed is text.
        $stored = MessageBody::fromPlainText($text);

        self::assertStringStartsWith('<p>', $stored);
        self::assertSame('</p>', substr($stored, -4));
        $inner = str_replace('<br>', '', substr($stored, 3, -4));
        foreach (['<', '>', '"', "'"] as $character) {
            self::assertStringNotContainsString($character, $inner);
        }
        //  Shown as HTML, it is exactly what the customer typed.
        self::assertSame(
            trim(str_replace(["\r\n", "\r"], "\n", $text)),
            html_entity_decode($inner, ENT_QUOTES | ENT_HTML5, 'UTF-8')
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

    public function testCdataIsWrittenAsEscapedText(): void
    {
        //  DOMCdataSection extends DOMText and saveHTML() writes its data unescaped. libxml2 makes CDATA
        //  from raw-text content (2.14+: xmp, noembed, noframes, plaintext), so the tree is built by hand to
        //  test the same thing whatever libxml2 this runs on.
        $document = new \DOMDocument('1.0', 'UTF-8');
        $root = $document->createElement('div');
        $document->appendChild($root);
        $paragraph = $document->createElement('p');
        $paragraph->appendChild($document->createCDATASection('<img src=x onerror=alert(1)>'));
        $root->appendChild($paragraph);
        $unknown = $document->createElement('font');
        $unknown->appendChild($document->createCDATASection('<script>alert(2)</script>'));
        $root->appendChild($unknown);
        $root->appendChild($document->createCDATASection('<b onclick="x()">3</b>'));

        self::assertSame(
            '<p>&lt;img src=x onerror=alert(1)&gt;</p>&lt;script&gt;alert(2)&lt;/script&gt;'
            . '&lt;b onclick="x()"&gt;3&lt;/b&gt;',
            MessageBody::sanitizeChildren($root)
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function rawTextElements(): array
    {
        return [
            'xmp' => ['<xmp>raw <img src=x onerror=alert(1)></xmp>'],
            'noembed' => ['<noembed>raw <img src=x onerror=alert(1)></noembed>'],
            'noframes' => ['<noframes>raw <img src=x onerror=alert(1)></noframes>'],
            'plaintext' => ['<plaintext>raw <img src=x onerror=alert(1)>'],
            'listing' => ['<listing>raw <img src=x onerror=alert(1)></listing>'],
            'noscript' => ['<noscript>raw <img src=x onerror=alert(1)></noscript>'],
            'iframe' => ['<iframe>raw <img src=x onerror=alert(1)></iframe>'],
        ];
    }

    #[DataProvider('rawTextElements')]
    public function testRawTextElementsAreDroppedWithTheirContent(string $element): void
    {
        //  Dropped, not unwrapped: whether libxml2 parses the content as elements or (2.14+) as CDATA,
        //  none of it reaches the output.
        self::assertSame('<p>kept</p>', MessageBody::toHtml('<p>kept</p>' . $element));
        self::assertSame('kept', MessageBody::toText('<p>kept</p>' . $element));
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
