<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\VendorsGraphQl\Model\Vendor;

use Vnecoms\VendorsApi\Api\VendorRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Webapi\ServiceOutputProcessor;
use Vnecoms\VendorsApi\Api\Data\VendorInterface;

/**
 * Transform single customer data from object to in array format
 */
class ExtractVendorData
{
    /**
     * @var ServiceOutputProcessor
     */
    private $serviceOutputProcessor;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param ServiceOutputProcessor $serviceOutputProcessor
     * @param SerializerInterface $serializer
     */
    public function __construct(
        ServiceOutputProcessor $serviceOutputProcessor,
        SerializerInterface $serializer
    ) {
        $this->serviceOutputProcessor = $serviceOutputProcessor;
        $this->serializer = $serializer;
    }

    /**
     * @param VendorInterface $customer
     * @return array
     */
    public function execute(VendorInterface $vendor): array
    {
        $vendorData = $this->serviceOutputProcessor->process(
            $vendor,
            VendorRepositoryInterface::class,
            'getById'
        );
        return $vendorData;
    }
}
