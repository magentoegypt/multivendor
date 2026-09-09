<?php
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Model\Layer\Filter;

/**
 * "Minimum Rating" — the prototype's 4.5+ / 4+ / 3.5+ / All ratings block.
 *
 * `review_entity_summary.rating_summary` is a PERCENTAGE (0-100), so 4.5 stars
 * is 90. Rows exist per store and a store-0 row alongside; this reads the
 * current store and falls back to 0, which is the same pairing the rest of this
 * project has had to respect twice (see the review-ratings note in the theme).
 */
class Rating extends AbstractIdFilter
{
    /** Entity type 1 is `product` in `review_entity`. */
    private const ENTITY_TYPE_PRODUCT = 1;

    /**
     * @var array<string, array{stars: int, text: string, min: int}>
     */
    private const LEVELS = [
        '45'  => ['stars' => 4, 'text' => '4.5+', 'min' => 90],
        '40'  => ['stars' => 4, 'text' => '4+',   'min' => 80],
        '35'  => ['stars' => 3, 'text' => '3.5+', 'min' => 70],
        'all' => ['stars' => 0, 'text' => 'All ratings', 'min' => 0],
    ];

    /**
     * Set as a PROPERTY, not in _construct(). AbstractFilter extends
     * DataObject, which has no _construct() hook — only AbstractModel does —
     * so a _requestVar assigned there is never run. It stayed null, and
     * `$request->getParam(null)` then returned null on every request: the
     * block rendered its options and filtered nothing. Core's own Price and
     * Category filters declare it exactly this way.
     */
    protected $_requestVar = 'rating';

    public function getName(): \Magento\Framework\Phrase|string
    {
        return __('Minimum Rating');
    }

    /**
     * Labels carry MARKUP, so the stars can be gold and the threshold dark, as
     * the prototype draws them. Mageplaza's filter template prints the label
     * unescaped (`/** @noEscape *\/ $filterItem->getLabel()`), and the
     * active-filter chip runs it through `stripTags()` first — so the option
     * list gets the spans and the chip gets "★★★★☆ 4.5+" as plain text.
     *
     * Nothing here comes from a request: the star counts and the wording are
     * the constant above, and the only translated part is escaped by __() the
     * same as anywhere else.
     */
    protected function hmOptions(): array
    {
        $out = [];

        foreach (self::LEVELS as $value => $level) {
            $text = (string) __($level['text']);

            if ($level['stars'] < 1) {
                $out[$value] = $text;
                continue;
            }

            $out[$value] = '<span class="hm-fstars" aria-hidden="true">'
                . '<span class="hm-fstars__on">' . str_repeat('★', $level['stars']) . '</span>'
                . '<span class="hm-fstars__off">' . str_repeat('★', 5 - $level['stars']) . '</span>'
                . '</span> <span class="hm-fstars__text">' . $text . '</span>';
        }

        return $out;
    }

    protected function hmIdsFor(string $value): array
    {
        $min = self::LEVELS[$value]['min'] ?? null;

        if ($min === null) {
            return [];
        }

        $conn = $this->hmResource->getConnection();
        $select = $conn->select()
            ->from(['s' => $this->hmResource->getTableName('review_entity_summary')], ['entity_pk_value'])
            ->where('s.entity_type = ?', self::ENTITY_TYPE_PRODUCT)
            ->where('s.store_id IN (?)', [0, $this->hmStoreId()])
            ->where('s.reviews_count > 0')
            ->distinct(true);

        //  "All ratings" is every REVIEWED product, not every product — it is
        //  the reset option inside this block, and the block only exists for
        //  products that carry a rating at all.
        if ($min > 0) {
            $select->where('s.rating_summary >= ?', $min);
        }

        return array_map('intval', $conn->fetchCol($select));
    }
}
