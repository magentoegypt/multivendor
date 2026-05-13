<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\VendorsSalesGraphQl\Model\Shipment;

use Vnecoms\VendorsApi\Api\ShipmentRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthenticationException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Framework\Api\DataObjectHelper;
use Vnecoms\VendorsApi\Api\Data\Sale\ItemQtyInterfaceFactory;
use Vnecoms\VendorsApi\Api\Data\Sale\TrackingInterfaceFactory;

/**
 * Get vendor
 */
class CreateShipment
{
    /**
     * @var ShipmentRepositoryInterface
     */
    private $shipmentRepository;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var ItemQtyInterfaceFactory
     */
    private $itemQtyInterfaceFactory;

    /**
     * @var TrackingInterfaceFactory
     */
    private $trackingInterfaceFactory;

    /**
     * CreateShipment constructor.
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param DataObjectHelper $dataObjectHelper
     * @param ItemQtyInterfaceFactory $itemQtyInterfaceFactory
     * @param TrackingInterfaceFactory $trackingInterfaceFactory
     */
    public function __construct(
        ShipmentRepositoryInterface $shipmentRepository,
        DataObjectHelper $dataObjectHelper,
        ItemQtyInterfaceFactory $itemQtyInterfaceFactory,
        TrackingInterfaceFactory $trackingInterfaceFactory
    ) {
        $this->shipmentRepository = $shipmentRepository;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->itemQtyInterfaceFactory = $itemQtyInterfaceFactory;
        $this->trackingInterfaceFactory = $trackingInterfaceFactory;
    }

    /**
     * @param ContextInterface $context
     * @param $args
     * @return mixed
     */
    public function execute(ContextInterface $context, $args)
    {
        $currentUserId = $context->getUserId();
        $newItems = [];
        $items = $args["items"];
        foreach ($items as $item) {
            $itemData = $this->itemQtyInterfaceFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $itemData,
                $item,
                'Vnecoms\VendorsApi\Api\Data\Sale\ItemQtyInterface'
            );
            $newItems[] = $itemData;
        }

        $newTracks = [];
        if (isset($args["tracking"])) {
            $tracks = $args["tracking"];
            foreach ($tracks as $track) {
                $trackData = $this->trackingInterfaceFactory->create();
                $this->dataObjectHelper->populateWithArray(
                    $trackData,
                    $track,
                    'Vnecoms\VendorsApi\Api\Data\Sale\TrackingInterface'
                );
                $newTracks[] = $trackData;
            }
        }
        try {
            $shipment = $this->shipmentRepository->createShipment(
                $currentUserId,
                $args["vendor_order_id"],
                $newItems,
                $args["comment"],
                $newTracks
            );
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

        return $shipment;
    }
}
