<?php
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Model\Layer\Filter;

/**
 * "Vendors" — the prototype's list of seller names.
 *
 * `catalog_product_entity.vendor_id` is a STATIC column (not an EAV attribute)
 * holding `ves_vendor_entity.entity_id`, and the display name is
 * `ves_vendor_entity.company`, a plain column rather than one of the module's
 * EAV attributes. `ves_vendor_entity` has no url_key column to fall back to, so
 * the four rows of twenty-five that leave `company` empty are simply not offered
 * as a filter option. That mapping is the same one
 * `MagentoEgypt\HomeSections\ViewModel\VendorNames` documents and relies on for
 * the vendor line on every product card.
 *
 * Products created in admin rather than by a seller carry vendor_id 0 and are
 * not offered as a vendor.
 */
class Vendor extends AbstractIdFilter
{
    /** @var array<string, string>|null */
    private ?array $hmVendors = null;

    /**
     * Set as a PROPERTY, not in _construct(). AbstractFilter extends
     * DataObject, which has no _construct() hook — only AbstractModel does —
     * so a _requestVar assigned there is never run. It stayed null, and
     * `$request->getParam(null)` then returned null on every request: the
     * block rendered its options and filtered nothing. Core's own Price and
     * Category filters declare it exactly this way.
     */
    protected $_requestVar = 'vendor';

    public function getName(): \Magento\Framework\Phrase|string
    {
        return __('Vendors');
    }

    protected function hmOptions(): array
    {
        if ($this->hmVendors !== null) {
            return $this->hmVendors;
        }

        $conn = $this->hmResource->getConnection();

        try {
            $rows = $conn->fetchAll(
                $conn->select()
                    ->from(['v' => $this->hmResource->getTableName('ves_vendor_entity')], ['entity_id', 'company'])
                    ->order('company ASC')
            );
        } catch (\Throwable $e) {
            return $this->hmVendors = [];
        }

        $out = [];

        foreach ($rows as $row) {
            $id = (int) $row['entity_id'];

            if ($id < 1) {
                continue;
            }
            $name = trim((string) ($row['company'] ?? ''));

            if ($name === '') {
                continue;
            }
            $out[(string) $id] = $name;
        }

        return $this->hmVendors = $out;
    }

    /**
     * One grouped query rather than one per seller.
     *
     * @param int[] $scopeIds
     * @return array<string, int>
     */
    protected function hmCounts(array $scopeIds): array
    {
        if (!$scopeIds) {
            return [];
        }

        $conn = $this->hmResource->getConnection();
        $rows = $conn->fetchPairs(
            $conn->select()
                ->from(
                    ['e' => $this->hmResource->getTableName('catalog_product_entity')],
                    ['vendor_id', 'total' => new \Zend_Db_Expr('COUNT(*)')]
                )
                ->where('e.entity_id IN (?)', $scopeIds)
                ->where('e.vendor_id > 0')
                ->group('e.vendor_id')
        );

        $out = [];

        foreach ($rows as $vendorId => $total) {
            $out[(string) (int) $vendorId] = (int) $total;
        }

        return $out;
    }

    protected function hmIdsFor(string $value): array
    {
        $vendorId = (int) $value;

        if ($vendorId < 1) {
            return [];
        }

        $conn = $this->hmResource->getConnection();
        $select = $conn->select()
            ->from(['e' => $this->hmResource->getTableName('catalog_product_entity')], ['entity_id'])
            ->where('e.vendor_id = ?', $vendorId);

        return array_map('intval', $conn->fetchCol($select));
    }
}
