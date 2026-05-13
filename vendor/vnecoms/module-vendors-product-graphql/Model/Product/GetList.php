<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\VendorsProductGraphQl\Model\Product;

use Vnecoms\VendorsApi\Api\ProductRepositoryInterface;
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
//use Vnecoms\VendorsApi\Api\Data\Sale\OrderInterface;

/**
 * Get vendor product
 */
class GetList
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var Builder
     */
    private $builder;

    /**
     * @var ExtensibleDataObjectConverter
     */
    private $dataObjectConverter;

    /**
     * GetList constructor.
     * @param ProductRepositoryInterface $productRepository
     * @param Builder $builder
     * @param ExtensibleDataObjectConverter $dataObjectConverter
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        Builder $builder,
        ExtensibleDataObjectConverter $dataObjectConverter
    ) {
        $this->productRepository = $productRepository;
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
        $searchCriteria = $this->builder->build('vendorProducts', $args);
        $searchCriteria->setCurrentPage($args['currentPage']);
        $searchCriteria->setPageSize($args['pageSize']);

        $searchResults = $this->productRepository->getList(
            $currentUserId,
            $searchCriteria
        );

        $orderArray = [];
        foreach ($searchResults->getItems() as $product) {
           // $orderData = $this->dataObjectConverter->toNestedArray($order, [], OrderInterface::class);
            $orderArray[$product->getEntityId()] = $product->getData();
            $orderArray[$product->getEntityId()]['model'] = $product;
        }

        $totalPages = $searchCriteria->getPageSize() ? ((int)ceil($searchResults->getTotalCount() / $searchCriteria->getPageSize())) : 0;

        return [
            'totalCount' => $searchResults->getTotalCount(),
            'items' => $orderArray,
            'pageSize' => $searchCriteria->getPageSize(),
            'currentPage' => $searchCriteria->getCurrentPage(),
            'totalPages' => $totalPages,
        ];
    }
}
