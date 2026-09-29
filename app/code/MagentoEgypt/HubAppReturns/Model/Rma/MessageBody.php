<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Model\Rma;

/**
 * Return messages between the app (plain text) and ves_rma_request_message.message (HTML).
 *
 * The website writes messages with a WYSIWYG editor and prints them unescaped
 * (Vnecoms_VendorsRMA::request/message/list.phtml), plain-text ones inside <pre>. The app sends
 * plain text, stored escaped as <p> with <br> so the admin, seller and customer pages show it as
 * written; it reads messages back through an allow-list sanitiser (HmReturnMessage.body_html) and
 * as plain text (body_text). Nothing stored is ever handed to the app unsanitised.
 */
final class MessageBody
{
    /** Elements kept (without attributes, apart from a safe href on links). */
    private const ALLOWED = [
        'a', 'b', 'blockquote', 'br', 'code', 'div', 'em', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'i',
        'li', 'ol', 'p', 'pre', 's', 'span', 'strong', 'table', 'tbody', 'td', 'th', 'thead', 'tr', 'u', 'ul',
    ];

    /** Elements removed together with everything inside them. Any other element is unwrapped. */
    private const DROPPED = [
        'applet', 'audio', 'base', 'button', 'canvas', 'embed', 'form', 'frame', 'frameset', 'head', 'iframe',
        'img', 'input', 'link', 'math', 'meta', 'noscript', 'object', 'option', 'script', 'select', 'style',
        'svg', 'template', 'textarea', 'title', 'video',
    ];

    /**
     * What the app sent, as the HTML stored for the website's pages.
     */
    public static function fromPlainText(string $text): string
    {
        $text = trim(str_replace(["\r\n", "\r"], "\n", $text));

        return '<p>' . nl2br(htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8'), false) . '</p>';
    }

    /**
     * A stored message as sanitised HTML (a plain-text message is escaped, line breaks kept).
     */
    public static function toHtml(string $stored): string
    {
        if (trim($stored) === '') {
            return '';
        }
        if ($stored === strip_tags($stored)) {
            return self::fromPlainText(html_entity_decode($stored, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        return self::sanitize($stored);
    }

    /**
     * A stored message as plain text: line breaks for <br> and block ends, entities decoded.
     */
    public static function toText(string $stored): string
    {
        $html = self::toHtml($stored);
        $html = (string) preg_replace('~<br\s*/?>~i', "\n", $html);
        $html = (string) preg_replace('~</(?:p|div|li|blockquote|pre|h[1-6]|tr)\s*>~i', "\n", $html);
        $html = (string) preg_replace('~<li(?:\s[^>]*)?>~i', '- ', $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\u{00A0}", ' ', $text);

        $lines = [];
        foreach (explode("\n", $text) as $line) {
            $lines[] = trim((string) preg_replace('/[ \t]+/u', ' ', $line));
        }

        return trim((string) preg_replace("/\n{3,}/", "\n\n", implode("\n", $lines)));
    }

    /**
     * Allow-list sanitiser: known formatting elements without attributes, links only to http(s) or
     * mailto, scripts/styles/embeds/images removed with their content, anything else unwrapped.
     */
    public static function sanitize(string $html): string
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">'
            . '</head><body><div>' . $html . '</div></body></html>',
            LIBXML_NONET
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $document->getElementsByTagName('body')->item(0);
        $root = $body !== null ? $body->firstChild : null;
        if (!$root instanceof \DOMElement) {
            return '';
        }
        self::clean($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= (string) $document->saveHTML($child);
        }

        return trim($out);
    }

    private static function clean(\DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof \DOMText) {
                continue;
            }
            if (!$child instanceof \DOMElement) {
                //  Comments, processing instructions, CDATA.
                $node->removeChild($child);
                continue;
            }
            $tag = strtolower($child->tagName);
            if (in_array($tag, self::DROPPED, true)) {
                $node->removeChild($child);
                continue;
            }
            self::clean($child);
            if (!in_array($tag, self::ALLOWED, true)) {
                while ($child->firstChild !== null) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }
            foreach (iterator_to_array($child->attributes) as $attribute) {
                /** @var \DOMAttr $attribute */
                $keep = $tag === 'a' && strtolower($attribute->name) === 'href'
                    && preg_match('~^\s*(?:https?:|mailto:)~i', $attribute->value) === 1;
                if (!$keep) {
                    $child->removeAttribute($attribute->name);
                }
            }
        }
    }
}
