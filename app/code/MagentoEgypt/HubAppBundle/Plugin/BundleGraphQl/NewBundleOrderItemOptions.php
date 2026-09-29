<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppBundle\Plugin\BundleGraphQl;

use Magento\BundleGraphQl\Model\Resolver\Order\Item\BundleOptions;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Sales\Model\Order\Creditmemo\Item as CreditmemoItem;
use Magento\Sales\Model\Order\Invoice\Item as InvoiceItem;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Model\Order\Shipment\Item as ShipmentItem;

/**
 * `bundle_options` of order, invoice, shipment and credit memo lines for `new_bundle`.
 *
 * BundleGraphQl's resolver builds the options only when the order line's
 * product_type is exactly `bundle`, so a new_bundle order listed its options as
 * []. The line's data is the same as a bundle's (BundleExtend orders it through
 * the core bundle type: bundle_options on the parent, bundle_selection_attributes
 * on the children), so the resolver is handed a CLONE of the line typed
 * `bundle`. The loaded line itself is never changed. For invoice, shipment and
 * credit memo lines, whose options are read from their order line, the clone
 * carries a bundle-typed clone of that order line.
 *
 * (The resolver's work is deferred: the clone is what its closure captures.)
 */
class NewBundleOrderItemOptions
{
    /**
     * @param BundleOptions $subject
     * @param callable $proceed
     * @param Field $field
     * @param mixed $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundResolve(
        BundleOptions $subject,
        callable $proceed,
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
    ) {
        $model = is_array($value) ? ($value['model'] ?? null) : null;

        if ($model instanceof OrderItem) {
            if ($model->getProductType() === TreatNewBundleAsBundle::NEW_BUNDLE) {
                $value['model'] = $this->asBundle($model);
            }
        } elseif ($model instanceof InvoiceItem || $model instanceof ShipmentItem || $model instanceof CreditmemoItem) {
            $orderItem = $model->getOrderItem();
            if ($orderItem instanceof OrderItem
                && $orderItem->getProductType() === TreatNewBundleAsBundle::NEW_BUNDLE
            ) {
                $line = clone $model;
                $line->setOrderItem($this->asBundle($orderItem));
                $value['model'] = $line;
            }
        }

        return $proceed($field, $context, $info, $value, $args);
    }

    private function asBundle(OrderItem $item): OrderItem
    {
        $copy = clone $item;
        $copy->setProductType(TreatNewBundleAsBundle::BUNDLE);

        return $copy;
    }
}
