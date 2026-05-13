<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\VendorsCreditGraphQl\Model\Credit;

use Vnecoms\VendorsApi\Api\WithdrawalRepositoryInterface;
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
use Vnecoms\VendorsApi\Api\Data\Credit\WithdrawalInterface;

/**
 * Get vendor
 */
class GetListWithdrawal
{
    /**
     * @var WithdrawalRepositoryInterface
     */
    private $withdrawalRepository;

    /**
     * @var NotificationRepositoryInterface
     */
    private $builder;

    /**
     * @var ExtensibleDataObjectConverter
     */
    private $dataObjectConverter;

    /**
     * GetListTransaction constructor.
     * @param WithdrawalRepositoryInterface $withdrawalRepository
     * @param Builder $builder
     * @param ExtensibleDataObjectConverter $dataObjectConverter
     */
    public function __construct(
        WithdrawalRepositoryInterface $withdrawalRepository,
        Builder $builder,
        ExtensibleDataObjectConverter $dataObjectConverter
    ) {
        $this->withdrawalRepository = $withdrawalRepository;
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
        $searchCriteria = $this->builder->build('vendor_withdrawal_transaction', $args);
        $searchCriteria->setCurrentPage($args['currentPage']);
        $searchCriteria->setPageSize($args['pageSize']);

        $searchResults = $this->withdrawalRepository->getList(
            $currentUserId,
            $searchCriteria
        );

        $transactionArray = [];
        foreach ($searchResults->getItems() as $transaction) {
            $transactionData["withdrawal_id"] = $transaction->getId();
            $transactionArray[$transaction->getId()] = $transaction->getData();
            $transactionArray[$transaction->getId()]['model'] = $transaction;
        }

        $totalPages = $searchCriteria->getPageSize() ? ((int)ceil($searchResults->getTotalCount() / $searchCriteria->getPageSize())) : 0;

        return [
            'totalCount' => $searchResults->getTotalCount(),
            'items' => $transactionArray,
            'pageSize' => $searchCriteria->getPageSize(),
            'currentPage' => $searchCriteria->getCurrentPage(),
            'totalPages' => $totalPages,
        ];
    }
}
