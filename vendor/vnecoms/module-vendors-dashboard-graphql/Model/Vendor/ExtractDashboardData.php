<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\VendorsDashboardGraphQl\Model\Vendor;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Webapi\ServiceOutputProcessor;
use Vnecoms\VendorsApi\Api\Data\VendorInterface;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Vnecoms\VendorsApi\Api\Data\Dashboard\DashboardInterface;

/**
 * Transform single customer data from object to in array format
 */
class ExtractDashboardData
{
    /**
     * @var SerializerInterface
     */
    private $dataObjectConverter;

    /**
     * ExtractDashboardData constructor.
     * @param ExtensibleDataObjectConverter $dataObjectConverter
     */
    public function __construct(
        ExtensibleDataObjectConverter $dataObjectConverter
    ) {
        $this->dataObjectConverter = $dataObjectConverter;
    }

    /**
     * @param DashboardInterface $dashboard
     * @return array
     */
    public function execute(DashboardInterface $dashboard): array
    {
        $dashboardData = $this->dataObjectConverter->toNestedArray(
            $dashboard,
            [],
            DashboardInterface::class
        );
        return $dashboardData;
    }
}
