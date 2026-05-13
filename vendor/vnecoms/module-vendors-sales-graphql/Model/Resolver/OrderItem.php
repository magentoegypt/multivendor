<?php

declare(strict_types=1);

namespace Vnecoms\VendorsSalesGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Json\Helper\Data;
use Magento\Sales\Model\Order\ItemFactory;

/**
 * Class OrderItem
 *
 * @package Ecommage\AdminSalesGraphQl\Model\Resolver
 */
class OrderItem implements ResolverInterface
{
    /**
     * @var ItemFactory
     */
    protected $itemFactory;

    /**
     * @var Data
     */
    private $jsonHelper;

    /**
     * OrderItem constructor.
     *
     * @param ItemFactory $itemFactory
     * @param Data        $jsonHelper
     */
    public function __construct(
        ItemFactory $itemFactory,
        Data $jsonHelper
    ) {
        $this->itemFactory = $itemFactory;
        $this->jsonHelper  = $jsonHelper;
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
        $orderItem = $this->itemFactory->create()->load($value['order_item_id']);
        $orderItem->setData("product_options", $this->jsonHelper->jsonEncode($orderItem->getProductOptions()));
        return $orderItem->getData();
    }
}
