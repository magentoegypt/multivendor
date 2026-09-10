<?php
declare(strict_types=1);

namespace MagentoEgypt\CheckoutExtend\Model\Pdf;

use Magento\Sales\Model\RtlTextHandler;

/**
 * Core's PDF RTL helper, with the reversal taken out.
 *
 * WHY. AbstractPdf::insertOrder runs each address line through
 * RtlTextHandler::reverseRtlText before drawing it. That reverses without
 * shaping — which is the original bug — and it now runs BEFORE ArabicPage gets
 * the string, so the page was handed text that had already been turned around
 * and shaped it in the wrong context. On the credit memo that showed up as the
 * country reading "رصم" while every other line on the page was correct.
 *
 * Direction is not something two components can each own a piece of. ArabicPage
 * shapes and orders every string on the document, so this one stands down.
 *
 * The class is not dead weight and is not a stub for its own sake: it keeps
 * `isRtlText` intact, and it is preferred over core rather than deleted so that
 * any future core caller still gets a working object.
 *
 * Scope check before doing this: reverseRtlText has exactly two callers in
 * 2.4.8 — AbstractPdf's address block and DefaultInvoice's item name. Both are
 * PDF paths this module now shapes itself, so nothing else changes behaviour.
 */
class NoRtlReversal extends RtlTextHandler
{
    /**
     * @param string $string
     * @return string
     */
    public function reverseRtlText(string $string): string
    {
        return $string;
    }
}
