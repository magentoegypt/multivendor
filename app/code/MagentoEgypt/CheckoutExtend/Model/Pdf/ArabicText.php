<?php
declare(strict_types=1);

namespace MagentoEgypt\CheckoutExtend\Model\Pdf;

/**
 * Arabic shaping and visual reordering for Magento's PDF documents.
 *
 * WHY THIS EXISTS, and why it is not what CL036-DEV01.23 asked for.
 *
 * That ticket says the invoice needs "an Arabic UTF-8 compliant font (e.g.
 * Amiri, Cairo, or Tajawal)" embedded. It does not. Magento draws its PDFs with
 * lib/internal/GnuFreeFont/FreeSerif.ttf, which is already embedded and which
 * already carries Arabic — 252 codepoints of U+0600-06FF and, decisively, 141
 * of Presentation Forms-B (U+FE70-FEFF). Verified by reading the font's cmap.
 *
 * The fault is the other half of the ticket: Zend_Pdf has no text engine. It
 * maps codepoints to glyphs and lays them left to right, in the order given. So
 * Arabic comes out with every letter in its ISOLATED form — the "disconnected"
 * in the report — and running the wrong way — the "reversed".
 *
 * Both are fixable here, in PHP, because the font already has the joined forms:
 *
 *   1. SHAPE. Pick each letter's contextual form (isolated / initial / medial /
 *      final) from its neighbours' joining behaviour, and fold the four
 *      lam-alef pairs into their ligatures.
 *   2. REORDER. Emit the glyphs in visual order, because the renderer will not
 *      do it. Latin and digit runs keep their own direction, so "1كجم" and a
 *      SKU inside an Arabic name still read correctly.
 *
 * This is a presentation-layer transform and belongs nowhere near the
 * storefront: the text it returns is glyph codes, not the characters the
 * catalogue stores. Search, sorting and every other consumer must keep seeing
 * the original. It is applied at the PDF drawing call and nowhere else.
 *
 * NO CONSTRUCTOR DEPENDENCIES, deliberately — this store runs production mode
 * against a compiled DI config, and a class with dependencies that the compiled
 * config has never seen is handed nulls.
 */
class ArabicText
{
    /**
     * letter => [isolated, final, initial, medial]
     *
     * Two entries mean the letter only ever joins to its right, so it has no
     * initial or medial form — that is what breaks a word into visual clusters
     * and it is a property of the alphabet, not a gap in this table.
     */
    private const FORMS = [
        0x0621 => [0xFE80],
        0x0622 => [0xFE81, 0xFE82],
        0x0623 => [0xFE83, 0xFE84],
        0x0624 => [0xFE85, 0xFE86],
        0x0625 => [0xFE87, 0xFE88],
        0x0626 => [0xFE89, 0xFE8A, 0xFE8B, 0xFE8C],
        0x0627 => [0xFE8D, 0xFE8E],
        0x0628 => [0xFE8F, 0xFE90, 0xFE91, 0xFE92],
        0x0629 => [0xFE93, 0xFE94],
        0x062A => [0xFE95, 0xFE96, 0xFE97, 0xFE98],
        0x062B => [0xFE99, 0xFE9A, 0xFE9B, 0xFE9C],
        0x062C => [0xFE9D, 0xFE9E, 0xFE9F, 0xFEA0],
        0x062D => [0xFEA1, 0xFEA2, 0xFEA3, 0xFEA4],
        0x062E => [0xFEA5, 0xFEA6, 0xFEA7, 0xFEA8],
        0x062F => [0xFEA9, 0xFEAA],
        0x0630 => [0xFEAB, 0xFEAC],
        0x0631 => [0xFEAD, 0xFEAE],
        0x0632 => [0xFEAF, 0xFEB0],
        0x0633 => [0xFEB1, 0xFEB2, 0xFEB3, 0xFEB4],
        0x0634 => [0xFEB5, 0xFEB6, 0xFEB7, 0xFEB8],
        0x0635 => [0xFEB9, 0xFEBA, 0xFEBB, 0xFEBC],
        0x0636 => [0xFEBD, 0xFEBE, 0xFEBF, 0xFEC0],
        0x0637 => [0xFEC1, 0xFEC2, 0xFEC3, 0xFEC4],
        0x0638 => [0xFEC5, 0xFEC6, 0xFEC7, 0xFEC8],
        0x0639 => [0xFEC9, 0xFECA, 0xFECB, 0xFECC],
        0x063A => [0xFECD, 0xFECE, 0xFECF, 0xFED0],
        0x0641 => [0xFED1, 0xFED2, 0xFED3, 0xFED4],
        0x0642 => [0xFED5, 0xFED6, 0xFED7, 0xFED8],
        0x0643 => [0xFED9, 0xFEDA, 0xFEDB, 0xFEDC],
        0x0644 => [0xFEDD, 0xFEDE, 0xFEDF, 0xFEE0],
        0x0645 => [0xFEE1, 0xFEE2, 0xFEE3, 0xFEE4],
        0x0646 => [0xFEE5, 0xFEE6, 0xFEE7, 0xFEE8],
        0x0647 => [0xFEE9, 0xFEEA, 0xFEEB, 0xFEEC],
        0x0648 => [0xFEED, 0xFEEE],
        0x0649 => [0xFEEF, 0xFEF0],
        0x064A => [0xFEF1, 0xFEF2, 0xFEF3, 0xFEF4],
    ];

    /** lam + alef, which must be drawn as one glyph: [isolated, final] */
    private const LAM_ALEF = [
        0x0622 => [0xFEF5, 0xFEF6],
        0x0623 => [0xFEF7, 0xFEF8],
        0x0625 => [0xFEF9, 0xFEFA],
        0x0627 => [0xFEFB, 0xFEFC],
    ];

    /** Combining marks. They hang off the previous letter and never join. */
    private const MARKS = [0x064B, 0x064C, 0x064D, 0x064E, 0x064F, 0x0650, 0x0651, 0x0652, 0x0653, 0x0654, 0x0655, 0x0670];

    /**
     * Does this string contain Arabic at all?
     *
     * The guard matters: everything else on these documents is Latin, and text
     * that needs no shaping must come back byte-identical.
     */
    public function isArabic(string $text): bool
    {
        return (bool) preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}]/u', $text);
    }

    /**
     * Shape and reorder a string for a renderer that has no text engine.
     */
    public function render(string $text): string
    {
        if ($text === '' || !$this->isArabic($text)) {
            return $text;
        }

        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $cps = array_map(static fn ($c) => self::ord($c), $chars);

        $shaped = $this->shape($cps);

        return $this->toVisualOrder($shaped);
    }

    /**
     * Contextual forms, plus the lam-alef ligatures.
     *
     * @param int[] $cps
     * @return int[]
     */
    private function shape(array $cps): array
    {
        $out = [];
        $count = count($cps);

        for ($i = 0; $i < $count; $i++) {
            $cp = $cps[$i];

            //  lam followed by one of the four alefs is a single glyph, and it
            //  has to be decided before the lam is given a form of its own.
            if ($cp === 0x0644 && isset($cps[$i + 1], self::LAM_ALEF[$cps[$i + 1]])) {
                $joinsBack = $this->joinsForward($this->prevLetter($cps, $i));
                $out[] = self::LAM_ALEF[$cps[$i + 1]][$joinsBack ? 1 : 0];
                $i++;   // the alef is consumed by the ligature
                continue;
            }

            if (!isset(self::FORMS[$cp])) {
                $out[] = $cp;
                continue;
            }

            $forms = self::FORMS[$cp];
            $prevJoins = $this->joinsForward($this->prevLetter($cps, $i));
            $nextJoins = $this->joinsBackward($this->nextLetter($cps, $i));
            $dual = count($forms) === 4;

            if ($prevJoins && $nextJoins && $dual) {
                $out[] = $forms[3];          // medial
            } elseif ($prevJoins) {
                $out[] = $forms[1];          // final
            } elseif ($nextJoins && $dual) {
                $out[] = $forms[2];          // initial
            } else {
                $out[] = $forms[0];          // isolated
            }
        }

        return $out;
    }

    /**
     * The previous letter, stepping over combining marks — a mark must not
     * break the join between the letters either side of it.
     *
     * @param int[] $cps
     */
    private function prevLetter(array $cps, int $i): ?int
    {
        for ($j = $i - 1; $j >= 0; $j--) {
            if (!in_array($cps[$j], self::MARKS, true)) {
                return $cps[$j];
            }
        }
        return null;
    }

    /** @param int[] $cps */
    private function nextLetter(array $cps, int $i): ?int
    {
        for ($j = $i + 1, $n = count($cps); $j < $n; $j++) {
            if (!in_array($cps[$j], self::MARKS, true)) {
                return $cps[$j];
            }
        }
        return null;
    }

    /** Can the character to my left reach me? Only dual-joining letters can. */
    private function joinsForward(?int $cp): bool
    {
        return $cp !== null && isset(self::FORMS[$cp]) && count(self::FORMS[$cp]) === 4;
    }

    /** Can the character to my right be reached? Every Arabic letter can. */
    private function joinsBackward(?int $cp): bool
    {
        return $cp !== null && (isset(self::FORMS[$cp]) || $cp === 0x0644);
    }

    /**
     * Emit glyphs in the order the renderer should paint them.
     *
     * The whole sequence is reversed, then any run that reads left-to-right in
     * its own right — Latin words, numbers, a SKU — is put back the way round
     * it started. Without that second pass "1كجم" would print as "مجك1".
     *
     * @param int[] $glyphs
     */
    private function toVisualOrder(array $glyphs): string
    {
        $glyphs = array_reverse($glyphs);

        $count = count($glyphs);
        for ($i = 0; $i < $count; $i++) {
            if (!$this->isLtr($glyphs[$i])) {
                continue;
            }
            $start = $i;
            while ($i + 1 < $count && ($this->isLtr($glyphs[$i + 1]) || $this->isLtrNeutral($glyphs, $i + 1))) {
                $i++;
            }
            if ($i > $start) {
                $slice = array_reverse(array_slice($glyphs, $start, $i - $start + 1));
                array_splice($glyphs, $start, $i - $start + 1, $slice);
            }
        }

        return implode('', array_map(static fn ($cp) => self::chr($cp), $glyphs));
    }

    /** Latin letters and digits carry their own direction. */
    private function isLtr(int $cp): bool
    {
        return ($cp >= 0x0030 && $cp <= 0x0039)      // 0-9
            || ($cp >= 0x0041 && $cp <= 0x005A)      // A-Z
            || ($cp >= 0x0061 && $cp <= 0x007A)      // a-z
            || ($cp >= 0x0660 && $cp <= 0x0669);     // Arabic-Indic digits
    }

    /**
     * A separator INSIDE a left-to-right run belongs to it — "12.50", "A-B".
     * One at the edge does not, so it is only neutral with an LTR character on
     * both sides.
     *
     * @param int[] $glyphs
     */
    private function isLtrNeutral(array $glyphs, int $i): bool
    {
        $neutral = [0x002E, 0x002C, 0x002D, 0x002F, 0x003A, 0x0025, 0x0027];
        if (!in_array($glyphs[$i], $neutral, true)) {
            return false;
        }
        return isset($glyphs[$i + 1]) && $this->isLtr($glyphs[$i + 1]);
    }

    private static function ord(string $char): int
    {
        $cp = mb_ord($char, 'UTF-8');
        return $cp === false ? 0x003F : $cp;
    }

    private static function chr(int $cp): string
    {
        $char = mb_chr($cp, 'UTF-8');
        return $char === false ? '?' : $char;
    }
}
