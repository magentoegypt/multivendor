<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\VendorsDashboardGraphQl\Model\Vendor;

use Vnecoms\VendorsApi\Api\DashboardRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthenticationException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\GraphQl\Model\Query\ContextInterface;
use Vnecoms\VendorsApi\Api\Data\Dashboard\DashboardInterface;

/**
 * Get vendor
 */
class GetDashboard
{
    /**
     * @var DashboardRepositoryInterface
     */
    private $vendorDashboardRepository;

    /**
     * GetVendor constructor.
     * @param DashboardRepositoryInterface $vendorDashboardRepository
     */
    public function __construct(
        DashboardRepositoryInterface $vendorDashboardRepository
    ) {
        $this->vendorDashboardRepository = $vendorDashboardRepository;
    }

    /**
     * @param ContextInterface $context
     * @return DashboardInterface
     */
    public function execute(ContextInterface $context, $args): DashboardInterface
    {
        $currentUserId = $context->getUserId();

        try {
            $dashboard = $this->vendorDashboardRepository->getDashboardInfo($currentUserId, $args['period']);
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

        return $dashboard;
    }
}
