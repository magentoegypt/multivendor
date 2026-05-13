<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\VendorsDashboardGraphQl\Model\Resolver;

use Vnecoms\VendorsDashboardGraphQl\Model\Vendor\GetDashboard;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\GraphQl\Model\Query\ContextInterface;
use Vnecoms\VendorsDashboardGraphQl\Model\Vendor\ExtractDashboardData;

/**
 * Customers field resolver, used for GraphQL request processing.
 */
class Dashboard implements ResolverInterface
{
    /**
     * @var GetDashboard
     */
    private $getDashboard;

    /**
     * @var ExtractDashboardData
     */
    private $extractDashboardData;

    /**
     * Dashboard constructor.
     * @param GetDashboard $getDashboard
     * @param ExtractDashboardData $extractDashboardData
     */
    public function __construct(
        GetDashboard $getDashboard,
        ExtractDashboardData $extractDashboardData
    ) {
        $this->getDashboard = $getDashboard;
        $this->extractDashboardData = $extractDashboardData;
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
        /** @var ContextInterface $context */
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current vendor isn\'t authorized.'));
        }

        if (!$args['period']) {
            throw new GraphQlInputException(__('Period must not null.'));
        }

        $dashboard = $this->getDashboard->execute($context, $args);
        return $this->extractDashboardData->execute($dashboard);;
    }
}
