<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\BatchRequestItemInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchResolverInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use MagentoEgypt\HubApp\Api\SellerSummaryProviderInterface;

/**
 * hm_seller on products, cart lines and order lines: ONE provider call for every
 * line of a response.
 *
 * Each concrete resolver only says which seller a line belongs to; this base
 * asks the seller summary provider once for all of them and hands each line
 * its summary. Vendor 0 is Hub Market; a seller that is missing or not
 * approved has no summary and the field is null.
 */
abstract class AbstractSellerResolver implements BatchResolverInterface
{
    public function __construct(
        private readonly SellerSummaryProviderInterface $sellerSummaries,
        protected readonly ProductVendorLookup $productVendors
    ) {
    }

    /**
     * @inheritDoc
     */
    public function resolve(ContextInterface $context, Field $field, array $requests): BatchResponse
    {
        $vendorIds = $this->vendorIds($requests);

        $wanted = [];
        foreach ($vendorIds as $vendorId) {
            if ($vendorId !== null) {
                $wanted[$vendorId] = $vendorId;
            }
        }
        $summaries = $wanted
            ? $this->sellerSummaries->getByVendorIds(
                array_values($wanted),
                (int) $context->getExtensionAttributes()->getStore()->getId()
            )
            : [];

        $response = new BatchResponse();
        foreach ($requests as $key => $request) {
            $vendorId = $vendorIds[$key] ?? null;
            $response->addResponse($request, $vendorId !== null ? ($summaries[$vendorId] ?? null) : null);
        }

        return $response;
    }

    /**
     * The seller of each line: vendor entity id (0 = Hub Market), or null when unknown.
     *
     * @param BatchRequestItemInterface[] $requests
     * @return array<int|string, int|null> same keys as $requests
     */
    abstract protected function vendorIds(array $requests): array;
}
