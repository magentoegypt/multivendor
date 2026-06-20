<?php
declare(strict_types=1);

namespace MagentoEgypt\SearchAttributeGuard\Plugin;

use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Catalog\Model\ResourceModel\Attribute as AttributeResource;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Model\AbstractModel;
use Psr\Log\LoggerInterface;

/**
 * Stops free-text (text / textarea) product attributes from being saved as
 * filterable / filterable-in-search.
 *
 * OpenSearch maps such attributes as `text`. Magento builds a layered-navigation
 * *terms aggregation* for every filterable / filterable-in-search attribute, and a
 * terms aggregation on a `text` field throws an OpenSearch illegal_argument_exception
 * ("set fielddata=true on [...]") -> "all shards failed" (400) -> EVERY storefront
 * search returns "no results". This has taken down search on this store more than once
 * (model_num, then model_num + dimensions). Forcing the flags off at the single save
 * chokepoint makes the misconfiguration impossible from the admin form, the API, and
 * data patches/imports.
 *
 * NOTE: used_for_sort_by is intentionally left untouched — Magento indexes a separate
 * `sort_<code>` keyword field for sortable attributes (which is why core `name`, a text
 * attribute, sorts fine), so it is not the failure mode here.
 */
class AttributeSaveGuard
{
    private const TEXT_INPUTS = ['text', 'textarea'];

    /** @var EavConfig */
    private $eavConfig;

    /** @var LoggerInterface */
    private $logger;

    /** @var int|null */
    private $productEntityTypeId;

    public function __construct(EavConfig $eavConfig, LoggerInterface $logger)
    {
        $this->eavConfig = $eavConfig;
        $this->logger = $logger;
    }

    /**
     * @param AttributeResource $subject
     * @param AbstractModel $object
     * @return array
     */
    public function beforeSave(AttributeResource $subject, AbstractModel $object): array
    {
        if (!in_array((string)$object->getData('frontend_input'), self::TEXT_INPUTS, true)) {
            return [$object];
        }
        if ((int)$object->getData('entity_type_id') !== $this->getProductEntityTypeId()) {
            return [$object];
        }

        $filterable = (int)$object->getData('is_filterable');
        $filterableInSearch = (int)$object->getData('is_filterable_in_search');
        if ($filterable === 0 && $filterableInSearch === 0) {
            return [$object];
        }

        $object->setData('is_filterable', 0);
        $object->setData('is_filterable_in_search', 0);

        $this->logger->warning(sprintf(
            'SearchAttributeGuard: neutralised is_filterable=%d / is_filterable_in_search=%d on free-text '
            . 'attribute "%s" (frontend_input=%s) — a terms aggregation on a text field breaks OpenSearch search.',
            $filterable,
            $filterableInSearch,
            (string)$object->getData('attribute_code'),
            (string)$object->getData('frontend_input')
        ));

        return [$object];
    }

    private function getProductEntityTypeId(): int
    {
        if ($this->productEntityTypeId === null) {
            $this->productEntityTypeId = (int)$this->eavConfig
                ->getEntityType(CatalogProduct::ENTITY)
                ->getId();
        }
        return $this->productEntityTypeId;
    }
}
