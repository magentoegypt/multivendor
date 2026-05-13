<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\VendorsCreditGraphQl\Model\Credit;

use Vnecoms\VendorsApi\Api\WithdrawalRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthenticationException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\GraphQl\Model\Query\ContextInterface;

/**
 * Get vendor
 */
class CreateWithdrawal
{
    /**
     * @var WithdrawalRepositoryInterface
     */
    private $withdrawalRepository;

    /**
     * GetVendor constructor.
     * @param WithdrawalRepositoryInterface $withdrawalRepository
     */
    public function __construct(
        WithdrawalRepositoryInterface $withdrawalRepository
    ) {
        $this->withdrawalRepository = $withdrawalRepository;
    }

    /**
     * @param ContextInterface $context
     * @param $args
     * @return mixed
     */
    public function execute(ContextInterface $context, $args)
    {
        $currentUserId = $context->getUserId();

        try {
            $withdrawalObject = $this->withdrawalRepository->createWithdrawal($currentUserId, [$args]);
            // @codeCoverageIgnoreStart
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(
                __('Vendor with id "%customer_id" does not exist.', ['customer_id' => $currentUserId]),
                $e
            );
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__($e->getMessage()));
            // @codeCoverageIgnoreEnd
        }
        return $withdrawalObject->getData();
    }
}
