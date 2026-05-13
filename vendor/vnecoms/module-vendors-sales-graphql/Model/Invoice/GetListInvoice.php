<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\VendorsSalesGraphQl\Model\Invoice;

use Vnecoms\VendorsApi\Api\InvoiceRepositoryInterface;
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
use Vnecoms\VendorsApi\Api\Data\Sale\InvoiceInterface;

/**
 * Get vendor
 */
class GetListInvoice
{
    /**
     * @var InvoiceRepositoryInterface
     */
    private $invoiceRepository;

    /**
     * @var Builder
     */
    private $builder;

    /**
     * @var ExtensibleDataObjectConverter
     */
    private $dataObjectConverter;

    /**
     * GetListOrder constructor.
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param Builder $builder
     * @param ExtensibleDataObjectConverter $dataObjectConverter
     */
    public function __construct(
        InvoiceRepositoryInterface $invoiceRepository,
        Builder $builder,
        ExtensibleDataObjectConverter $dataObjectConverter
    ) {
        $this->invoiceRepository = $invoiceRepository;
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
        $searchCriteria = $this->builder->build('vendorInvoices', $args);
        $searchCriteria->setCurrentPage($args['currentPage']);
        $searchCriteria->setPageSize($args['pageSize']);

        $searchResults = $this->invoiceRepository->getList(
            $currentUserId,
            $searchCriteria
        );

        $invoiceArray = [];
        foreach ($searchResults->getItems() as $invoice) {
            //$invoiceData = $this->dataObjectConverter->toNestedArray($invoice, [], InvoiceInterface::class);
            $invoiceArray[$invoice->getEntityId()] = $invoice->getData();
            $invoiceArray[$invoice->getEntityId()]['model'] = $invoice;
        }

        $totalPages = $searchCriteria->getPageSize() ? ((int)ceil($searchResults->getTotalCount() / $searchCriteria->getPageSize())) : 0;

        return [
            'totalCount' => $searchResults->getTotalCount(),
            'items' => $invoiceArray,
            'pageSize' => $searchCriteria->getPageSize(),
            'currentPage' => $searchCriteria->getCurrentPage(),
            'totalPages' => $totalPages,
        ];
    }
}
