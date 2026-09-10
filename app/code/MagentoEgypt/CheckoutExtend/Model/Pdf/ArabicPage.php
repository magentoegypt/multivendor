<?php
declare(strict_types=1);

namespace MagentoEgypt\CheckoutExtend\Model\Pdf;

use Magento\Framework\App\ObjectManager;

/**
 * A PDF page that shapes Arabic on its way to the canvas.
 *
 * Every string on a sales document — the column headers, the "Ship to:"
 * captions, the addresses, the totals, the item rows — reaches the renderer
 * through exactly one call: Zend_Pdf_Page::drawText(). Shaping it here covers
 * the whole document with one override, where reaching the same text through
 * AbstractPdf would mean forking `_drawHeader`, `insertOrder`, `insertAddress`
 * and `insertTotals` — all protected, several hundred lines of core, and a new
 * copy to reconcile at every upgrade.
 *
 * ArabicText is idempotent, so the item renderers can keep shaping their own
 * rows without this double-shaping them.
 *
 * The page is constructed without the document's element factory, which is what
 * `Zend_Pdf::newPage()` would have supplied. That is deliberate and tested: a
 * page given its own factory renders and saves correctly, and the alternative —
 * reaching into Zend_Pdf's protected internals — is a worse trade than letting
 * the page own its resources.
 */
class ArabicPage extends \Zend_Pdf_Page
{
    private ?ArabicText $arabicText = null;

    /**
     * Zend_Pdf_Page's constructor is polymorphic across three shapes, so the
     * signature is passed straight through rather than narrowed.
     *
     * @param mixed $param1
     * @param mixed $param2
     * @param mixed $param3
     */
    public function __construct($param1, $param2 = null, $param3 = null)
    {
        parent::__construct($param1, $param2, $param3);
        $this->arabicText = ObjectManager::getInstance()->get(ArabicText::class);
    }

    /**
     * @param string $text
     * @param float $x
     * @param float $y
     * @param string $charEncoding
     * @return \Zend_Pdf_Page
     * @throws \Zend_Pdf_Exception
     */
    public function drawText($text, $x, $y, $charEncoding = '')
    {
        //  NOT is_string(). Half of AbstractPdf draws with `__('Sold to:')`,
        //  which is a Magento\Framework\Phrase, not a string — Zend_Pdf casts
        //  it on the way in. A string test silently skipped exactly the labels
        //  the report is about: the column headers, "Ship to:", the payment and
        //  shipping captions. Anything stringable is cast here first.
        if ($this->arabicText !== null && (is_string($text) || $this->stringable($text))) {
            $cast = (string) $text;
            if ($cast !== '') {
                $text = $this->arabicText->render($cast);
            }
        }

        return parent::drawText($text, $x, $y, $charEncoding);
    }

    /**
     * @param mixed $value
     */
    private function stringable($value): bool
    {
        return is_object($value) && method_exists($value, '__toString');
    }
}
