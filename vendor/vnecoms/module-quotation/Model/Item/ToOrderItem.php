<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\Quotation\Model\Item;

use Magento\Framework\DataObject\Copy;
use Vnecoms\Quotation\Model\Item;
use Magento\Sales\Api\Data\OrderItemInterfaceFactory as OrderItemFactory;
use Magento\Sales\Api\Data\OrderItemInterface;

/**
 * Class ToOrderItem
 */
class ToOrderItem
{
    /**
     * @var Copy
     */
    protected $objectCopyService;

    /**
     * @var OrderItemFactory
     */
    protected $orderItemFactory;

    /**
     * @var \Magento\Framework\Api\DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @param OrderItemFactory $orderItemFactory
     * @param Copy $objectCopyService
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     */
    public function __construct(
        OrderItemFactory $orderItemFactory,
        Copy $objectCopyService,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
    ) {
        $this->orderItemFactory = $orderItemFactory;
        $this->objectCopyService = $objectCopyService;
        $this->dataObjectHelper = $dataObjectHelper;
    }

    /**
     * @param Item $item
     * @param array $data
     * @return OrderItemInterface
     */
    public function convert($item, $data = [])
    {
        $options = $item->getProductOrderOptions();
        if (!$options) {
            $options = $item->getProduct()->getTypeInstance()->getOrderOptions($item->getProduct());
        }
        $orderItemData = $this->objectCopyService->getDataFromFieldset(
            'quote_convert_item',
            'to_order_item',
            $item
        );


        $orderItem = $this->orderItemFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $orderItem,
            array_merge($orderItemData, $data),
            '\Magento\Sales\Api\Data\OrderItemInterface'
        );
        $orderItem->setProductOptions($options);
        if ($item->getParentItem()) {
            $orderItem->setQtyOrdered(
                $orderItemData[OrderItemInterface::QTY_ORDERED] * $item->getParentItem()->getQty()
            );
        }
        return $orderItem;
    }
}
