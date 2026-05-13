<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\VendorsSalesGraphQl\Model\Shipment;

use Vnecoms\VendorsApi\Api\ShipmentRepositoryInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthenticationException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Argument\SearchCriteria\Builder;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Vnecoms\VendorsApi\Api\Data\Sale\ShipmentInterface;

/**
 * Get vendor
 */
class GetListShipment
{
    /**
     * @var ShipmentRepositoryInterface
     */
    private $shipmentRepository;

    /**
     * @var Builder
     */
    private $builder;

    /**
     * @var ExtensibleDataObjectConverter
     */
    private $dataObjectConverter;

    /**
     * GetListShipment constructor.
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param Builder $builder
     * @param ExtensibleDataObjectConverter $dataObjectConverter
     */
    public function __construct(
        ShipmentRepositoryInterface $shipmentRepository,
        Builder $builder,
        ExtensibleDataObjectConverter $dataObjectConverter
    ) {
        $this->shipmentRepository = $shipmentRepository;
        $this->builder = $builder;
        $this->dataObjectConverter = $dataObjectConverter;
    }

    /**
     * @param $args
     * @param ResolveInfo $info
     * @param ContextInterface $context
     * @return mixed
     */
    public function execute($args, ResolveInfo $info, ContextInterface $context)
    {
        $currentUserId = $context->getUserId();
        $searchCriteria = $this->builder->build('vendorShipments', $args);
        $searchCriteria->setCurrentPage($args['currentPage']);
        $searchCriteria->setPageSize($args['pageSize']);

        $searchResults = $this->shipmentRepository->getList(
            $currentUserId,
            $searchCriteria
        );

        $shipmentArray = [];
        foreach ($searchResults->getItems() as $shipment) {
            //$shipmentData = $this->dataObjectConverter->toNestedArray($shipment, [], ShipmentInterface::class);
            $shipmentArray[$shipment->getEntityId()] = $shipment->getData();
            $shipmentArray[$shipment->getEntityId()]['model'] = $shipment;
        }

        $totalPages = $searchCriteria->getPageSize() ? ((int)ceil($searchResults->getTotalCount() / $searchCriteria->getPageSize())) : 0;

        return [
            'totalCount' => $searchResults->getTotalCount(),
            'items' => $shipmentArray,
            'pageSize' => $searchCriteria->getPageSize(),
            'currentPage' => $searchCriteria->getCurrentPage(),
            'totalPages' => $totalPages,
        ];
    }
}
