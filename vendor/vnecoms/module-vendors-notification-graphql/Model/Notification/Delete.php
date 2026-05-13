<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\VendorsNotificationGraphQl\Model\Notification;

use Vnecoms\VendorsApi\Api\NotificationRepositoryInterface;
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
class Delete
{
    /**
     * @var NotificationRepositoryInterface
     */
    private $notificationRepository;

    /**
     * GetVendor constructor.
     * @param NotificationRepositoryInterface $vendorRepository
     */
    public function __construct(
        NotificationRepositoryInterface $notificationRepository
    ) {
        $this->notificationRepository = $notificationRepository;
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
            $delete = $this->notificationRepository->deleteById($args['notification_id'], $currentUserId);
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

        return $delete;
    }
}
