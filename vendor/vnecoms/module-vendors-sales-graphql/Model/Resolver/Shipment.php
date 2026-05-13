<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\VendorsSalesGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Vnecoms\VendorsSalesGraphQl\Model\Shipment\GetListShipment;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;

/**
 * Products field resolver, used for GraphQL request processing.
 */
class Shipment implements ResolverInterface
{
    /**
     * @var GetListShipment
     */
    private $getListShipment;

    /**
     * Invoice constructor.
     * @param GetListShipment $getListShipment
     */
    public function __construct(
        GetListShipment $getListShipment
    ) {
        $this->getListShipment = $getListShipment;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current vendor isn\'t authorized.'));
        }

        if ($args['currentPage'] < 1) {
            throw new GraphQlInputException(__('currentPage value must be greater than 0.'));
        }
        if ($args['pageSize'] < 1) {
            throw new GraphQlInputException(__('pageSize value must be greater than 0.'));
        }

        $searchResult = $this->getListShipment->execute($args, $info, $context);

        if ($searchResult["currentPage"] > $searchResult["totalPages"] && $searchResult["totalCount"] > 0) {
            throw new GraphQlInputException(
                __(
                    'currentPage value %1 specified is greater than the %2 page(s) available.',
                    [$searchResult["currentPage"], $searchResult["totalPages"]]
                )
            );
        }

        return [
            'total_count' => $searchResult["totalCount"],
            'items' => $searchResult["items"],
            'page_info' => [
                'page_size' => $searchResult["pageSize"],
                'current_page' => $searchResult["currentPage"],
                'total_pages' => $searchResult["totalPages"],
            ],
        ];
    }
}
