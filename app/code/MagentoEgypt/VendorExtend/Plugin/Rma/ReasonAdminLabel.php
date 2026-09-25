<?php
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Plugin\Rma;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Vnecoms\RMA\Model\Reason;

/**
 * Admin shows RMA reasons in the admin user's own interface language.
 *
 * Vnecoms keeps one default title per reason plus per-store-view labels (ves_rma_reason_store), and the
 * admin (store 0) always gets the default title. On Hub Market the default titles were entered in Arabic
 * and the English wording lives in the English store view's labels, so English admins saw Arabic reasons
 * in Returns > New Request, in the returns grids (Reason column and its filter) and on the request page.
 *
 * Registered in etc/adminhtml/di.xml, so it only runs in the admin. Where Vnecoms asks for the admin store
 * (store 0) it answers with the label of a store view whose locale is the admin user's interface locale:
 * the first such view, default website first, that has a label for the reason (en_US skips the label-less
 * "vendors" view and lands on "en"). With no such label it keeps Vnecoms' default title, which is what an
 * ar_SA admin gets today. A store view passed explicitly is left alone, so an email sent in a customer's
 * store view keeps the customer's language.
 */
class ReasonAdminLabel
{
    /**
     * @var int[]|null Active store views whose locale is the admin user's, default website first
     */
    private $localeStoreIds;

    /**
     * @var array<int, array<int, string>>|null reason_id => [store_id => label]
     */
    private $labels;

    public function __construct(
        private readonly ResolverInterface $localeResolver,
        private readonly StoreManagerInterface $storeManager,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly ResourceConnection $resource
    ) {
    }

    /**
     * Request::getReasonTitle() and Reason::getOptionArray() pass the current store, which is 0 in the admin.
     *
     * @param Reason $subject
     * @param callable $proceed
     * @param int|string|null $storeId
     * @return string
     */
    public function aroundGetLabelByStoreId(Reason $subject, callable $proceed, $storeId = null)
    {
        if (!$storeId) {
            $label = $this->adminLabel((int) $subject->getId());
            if ($label !== null) {
                return $label;
            }
        }

        return $proceed($storeId);
    }

    /**
     * Returns > New Request builds its reason dropdown from toOptionArray(), which reads getTitle() (the
     * default title) directly, so relabel its options the same way.
     *
     * @param Reason $subject
     * @param array $result [['label' => ..., 'value' => reason id], ...]
     * @return array
     */
    public function afterToOptionArray(Reason $subject, $result)
    {
        if (!is_array($result)) {
            return $result;
        }
        foreach ($result as &$option) {
            $label = $this->adminLabel((int) ($option['value'] ?? 0));
            if ($label !== null) {
                $option['label'] = $label;
            }
        }

        return $result;
    }

    private function adminLabel(int $reasonId): ?string
    {
        if (!$reasonId) {
            return null;
        }
        $labels = $this->labels()[$reasonId] ?? [];
        foreach ($this->localeStoreIds() as $storeId) {
            if (isset($labels[$storeId]) && trim($labels[$storeId]) !== '') {
                return $labels[$storeId];
            }
        }

        return null;
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function labels(): array
    {
        if ($this->labels === null) {
            $this->labels = [];
            $connection = $this->resource->getConnection();
            $select = $connection->select()->from(
                $this->resource->getTableName('ves_rma_reason_store'),
                ['reason_id', 'store_id', 'title']
            );
            foreach ($connection->fetchAll($select) as $row) {
                $this->labels[(int) $row['reason_id']][(int) $row['store_id']] = (string) $row['title'];
            }
        }

        return $this->labels;
    }

    /**
     * @return int[]
     */
    private function localeStoreIds(): array
    {
        if ($this->localeStoreIds === null) {
            $this->localeStoreIds = [];
            $locale = (string) $this->localeResolver->getLocale();
            $defaultWebsiteId = (int) $this->storeManager->getDefaultStoreView()?->getWebsiteId();
            $stores = array_values($this->storeManager->getStores());
            usort($stores, static function ($a, $b) use ($defaultWebsiteId) {
                return [(int) $a->getWebsiteId() !== $defaultWebsiteId, (int) $a->getId()]
                    <=> [(int) $b->getWebsiteId() !== $defaultWebsiteId, (int) $b->getId()];
            });
            foreach ($stores as $store) {
                $storeLocale = $this->scopeConfig->getValue('general/locale/code', ScopeInterface::SCOPE_STORE, $store->getId());
                if ($store->isActive() && $storeLocale === $locale) {
                    $this->localeStoreIds[] = (int) $store->getId();
                }
            }
        }

        return $this->localeStoreIds;
    }
}
