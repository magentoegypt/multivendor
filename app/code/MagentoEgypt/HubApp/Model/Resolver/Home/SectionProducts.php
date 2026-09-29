<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Resolver\Home;

use Magento\CatalogGraphQl\Model\Resolver\Product\ProductFieldsSelector;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\BatchRequestItemInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchResolverInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use MagentoEgypt\HubApp\Api\ProductListLoaderInterface;

/**
 * HmHomeSection.products — every product section of a Home in ONE collection.
 *
 * The Home resolver leaves each product section's ranked ids under IDS_KEY;
 * this batch resolver collects them across all sections, loads them once with
 * the union of the fields the query asked for, and hands each section its own
 * slice back in rank order. A section without IDS_KEY is not a product
 * section and gets null.
 */
class SectionProducts implements BatchResolverInterface
{
    /** Where the Home resolver puts a section's ranked, gated product ids. */
    public const IDS_KEY = '_product_ids';

    private const NODE = 'products';

    public function __construct(
        private readonly ProductFieldsSelector $fieldsSelector,
        private readonly ProductListLoaderInterface $loader
    ) {
    }

    /**
     * @inheritDoc
     */
    public function resolve(ContextInterface $context, Field $field, array $requests): BatchResponse
    {
        $ids = [];
        $fields = [];
        /** @var BatchRequestItemInterface $request */
        foreach ($requests as $request) {
            $value = $request->getValue() ?? [];
            foreach ((array) ($value[self::IDS_KEY] ?? []) as $id) {
                $ids[(int) $id] = true;
            }
            $fields[] = $this->fieldsSelector->getProductFieldsFromInfo($request->getInfo(), self::NODE);
        }

        $byId = [];
        if ($ids) {
            $fields = array_values(array_unique(array_merge([], ...$fields)));
            foreach ($this->loader->load(array_keys($ids), $fields, $context) as $product) {
                $byId[(int) ($product['entity_id'] ?? 0)] = $product;
            }
        }

        $response = new BatchResponse();
        foreach ($requests as $request) {
            $value = $request->getValue() ?? [];
            if (!array_key_exists(self::IDS_KEY, $value)) {
                $response->addResponse($request, null);
                continue;
            }
            $products = [];
            foreach ((array) $value[self::IDS_KEY] as $id) {
                if (isset($byId[(int) $id])) {
                    $products[] = $byId[(int) $id];
                }
            }
            $response->addResponse($request, $products);
        }

        return $response;
    }
}
