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

    /** @var array<string, array{label: string, min: int}> */
    private const LEVELS = [
        '45' => ['label' => '★★★★★ 4.5+', 'min' => 90],
        '40' => ['label' => '★★★★☆ 4+',   'min' => 80],
        '35' => ['label' => '★★★☆☆ 3.5+', 'min' => 70],
        'all' => ['label' => 'All ratings', 'min' => 0],
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

    protected function hmOptions(): array
    {
        $out = [];

        foreach (self::LEVELS as $value => $level) {
            $out[$value] = (string) __($level['label']);
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
