<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\VendorsReportGraphQl\Model\Report;

use Vnecoms\VendorsApi\Api\ReportRepositoryInterface;
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
use Vnecoms\VendorsApi\Api\Data\Report\MostViewedInterface;
/**
 * Get vendor
 */
class GetListMostView
{
    /**
     * @var ReportRepositoryInterface
     */
    private $reportRepository;

    /**
     * @var Builder
     */
    private $builder;

    /**
     * @var ExtensibleDataObjectConverter
     */
    private $dataObjectConverter;

    /**
     * GetListBestSelling constructor.
     * @param ReportRepositoryInterface $reportRepository
     * @param Builder $builder
     * @param ExtensibleDataObjectConverter $dataObjectConverter
     */
    public function __construct(
        ReportRepositoryInterface $reportRepository,
        Builder $builder,
        ExtensibleDataObjectConverter $dataObjectConverter
    ) {
        $this->reportRepository = $reportRepository;
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

        $searchResults = $this->reportRepository->getMostViewed(
            $currentUserId,
            $args["limit"]
        );

        $reportArray = [];
        foreach ($searchResults->getItems() as $report) {
            $reportData = $this->dataObjectConverter->toFlatArray($report, [], MostViewedInterface::class);
            $reportArray[] = $reportData;
        }
        return $reportArray;
    }
}
