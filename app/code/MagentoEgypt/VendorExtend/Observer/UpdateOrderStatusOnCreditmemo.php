<?php
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Observer;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Psr\Log\LoggerInterface;

/**
 * After a credit memo is issued (partial or full), force both the parent
 * Magento order and the matching Vnecoms vendor order rows to the custom
 * "refunded" status so the admin "Manage Seller's Orders" grid and the
 * vendor backend reflect the refund instead of Vnecoms's default
 * "processing"/"closed".
 *
 * Registered against two events:
 *   - sales_order_creditmemo_save_after: fires for every credit memo save
 *     (admin, vendor controller, API, RMA, etc.). Handles the parent order
 *     and is the only path for non-controller flows.
 *   - vnecoms_vendorssales_order_creditmemo_save_after: fires only from the
 *     Vnecoms vendor/admin credit memo controllers AFTER
 *     Vnecoms\VendorsSales\Observer\ProcessCreditmemo has overwritten the
 *     vendor order status back to "processing"/"closed". Module sequence
 *     ensures we run after ProcessCreditmemo and can re-apply "refunded".
 */
class UpdateOrderStatusOnCreditmemo implements ObserverInterface
{
    public const REFUNDED_STATUS = 'refunded';

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ResourceConnection $resourceConnection,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        /** @var Creditmemo|null $creditmemo */
        $creditmemo = $observer->getEvent()->getCreditmemo();
        if (!$creditmemo) {
            return;
        }

        $order = $creditmemo->getOrder();
        if (!$order || !$order->getId()) {
            return;
        }

        try {
            $this->updateParentOrderStatus($order);
            $this->updateVendorOrderStatus($creditmemo, (int) $order->getId());
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf(
                    'VendorExtend: failed to update order #%s status to refunded: %s',
                    $order->getIncrementId(),
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * Set the parent Magento order's status to "refunded". State is left
     * unchanged so Magento's refund logic (processing for partial, closed
     * for full) keeps controlling availability of further actions.
     */
    private function updateParentOrderStatus(Order $order): void
    {
        if ($order->getStatus() === self::REFUNDED_STATUS) {
            return;
        }

        $order->setStatus(self::REFUNDED_STATUS);
        $order->addStatusHistoryComment(
            __('Order status changed to Refunded after credit memo was issued.')->render(),
            self::REFUNDED_STATUS
        );
        $order->save();
    }

    /**
     * Flip the matching ves_vendor_sales_order rows to status="refunded".
     * State is preserved because Vnecoms relies on it for canShip/canInvoice
     * /canCreditmemo logic.
     */
    private function updateVendorOrderStatus(Creditmemo $creditmemo, int $orderId): void
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('ves_vendor_sales_order');

        if (!$connection->isTableExists($tableName)) {
            return;
        }

        $where = ['order_id = ?' => $orderId];

        $vendorOrderId = (int) $creditmemo->getData('vendor_order_id');
        if ($vendorOrderId > 0) {
            $where['entity_id = ?'] = $vendorOrderId;
        } else {
            $vendorIds = $this->collectVendorIdsFromItems($creditmemo);
            if (!empty($vendorIds)) {
                $where['vendor_id IN (?)'] = $vendorIds;
            }
        }

        $connection->update(
            $tableName,
            ['status' => self::REFUNDED_STATUS],
            $where
        );
    }

    /**
     * Fallback: derive the affected vendor ids from the credit memo items
     * via sales_order_item.vendor_id. Used when the credit memo does not
     * carry a vendor_order_id (non-Vnecoms-controller flows).
     */
    private function collectVendorIdsFromItems(Creditmemo $creditmemo): array
    {
        $vendorIds = [];
        foreach ($creditmemo->getAllItems() as $creditmemoItem) {
            $orderItem = $creditmemoItem->getOrderItem();
            if (!$orderItem) {
                continue;
            }
            $vendorId = (int) $orderItem->getData('vendor_id');
            if ($vendorId > 0) {
                $vendorIds[$vendorId] = $vendorId;
            }
        }

        return array_values($vendorIds);
    }
}
