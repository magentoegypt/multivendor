<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\VendorsGraphQl\Model\Vendor;

use Vnecoms\VendorsApi\Api\VendorRepositoryInterface;
use Vnecoms\VendorsApi\Api\Data\VendorInterface;
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
class GetVendor
{
    /**
     * @var VendorRepositoryInterface
     */
    private $vendorRepository;

    /**
     * GetVendor constructor.
     * @param VendorRepositoryInterface $vendorRepository
     */
    public function __construct(
        VendorRepositoryInterface $vendorRepository
    ) {
        $this->vendorRepository = $vendorRepository;
    }

    /**
     * @param ContextInterface $context
     * @return VendorInterface
     */
    public function execute(ContextInterface $context): VendorInterface
    {
        $currentUserId = $context->getUserId();

        try {
            $vendor = $this->vendorRepository->getById($currentUserId);
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

        return $vendor;
    }
}
