<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\VendorsSalesGraphQl\Model\Invoice;

use Vnecoms\VendorsApi\Api\InvoiceRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthenticationException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Framework\Api\DataObjectHelper;
use Vnecoms\VendorsApi\Api\Data\Sale\ItemQtyInterfaceFactory;

/**
 * Get vendor
 */
class CreateInvoice
{
    /**
     * @var InvoiceRepositoryInterface
     */
    private $invoiceRepository;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var ItemQtyInterfaceFactory
     */
    private $itemQtyInterfaceFactory;

    /**
     * CreateInvoice constructor.
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param DataObjectHelper $dataObjectHelper
     * @param ItemQtyInterfaceFactory $itemQtyInterfaceFactory
     */
    public function __construct(
        InvoiceRepositoryInterface $invoiceRepository,
        DataObjectHelper $dataObjectHelper,
        ItemQtyInterfaceFactory $itemQtyInterfaceFactory
    ) {
        $this->invoiceRepository = $invoiceRepository;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->itemQtyInterfaceFactory = $itemQtyInterfaceFactory;
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
        try {
            $invoice = $this->invoiceRepository->createInvoice(
                $currentUserId,
                $args["vendor_order_id"],
                $newItems,
                $args["comment"]
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

        return $invoice;
    }
}
