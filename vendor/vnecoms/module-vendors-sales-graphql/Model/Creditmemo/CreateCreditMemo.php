<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\VendorsSalesGraphQl\Model\Creditmemo;

use Vnecoms\VendorsApi\Api\MemoRepositoryInterface;
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
class CreateCreditMemo
{
    /**
     * @var MemoRepositoryInterface
     */
    private $memoRepository;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var ItemQtyInterfaceFactory
     */
    private $itemQtyInterfaceFactory;

    /**
     * CreateCreditMemo constructor.
     * @param MemoRepositoryInterface $memoRepository
     * @param DataObjectHelper $dataObjectHelper
     * @param ItemQtyInterfaceFactory $itemQtyInterfaceFactory
     */
    public function __construct(
        MemoRepositoryInterface $memoRepository,
        DataObjectHelper $dataObjectHelper,
        ItemQtyInterfaceFactory $itemQtyInterfaceFactory
    ) {
        $this->memoRepository = $memoRepository;
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
            $memo = $this->memoRepository->createMemo(
                $currentUserId,
                $args["vendor_order_id"],
                $newItems,
                $args["comment"],
                $args["do_offline"]
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

        return $memo;
    }
}
