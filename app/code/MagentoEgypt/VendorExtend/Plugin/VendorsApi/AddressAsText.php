<?php
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Plugin\VendorsApi;

use Magento\Sales\Model\Order\Address;
use Magento\Sales\Model\Order\Address\Renderer;

/**
 * Seller API shipments and credit memos: the address as the one-line TEXT their interfaces declare.
 *
 * Vnecoms' ShipmentInterface / MemoInterface declare getBillingAddress() / getShippingAddress() as
 * `string`, but the repositories fill them with the order's Address object. The Web API output
 * processor then reflects the class "string" and GET /V1/vendors/order/shipment answered 500 for
 * every seller with a shipment (V8S2: 7); /order/memo has the same flaw as soon as a seller has a
 * credit memo. The core "oneline" format inserts field values as plain text.
 */
class AddressAsText
{
    public function __construct(private readonly Renderer $renderer)
    {
    }

    /**
     * @param mixed $subject
     * @param mixed $result
     * @return string|null
     */
    public function afterGetBillingAddress($subject, $result)
    {
        return $this->asText($result);
    }

    /**
     * @param mixed $subject
     * @param mixed $result
     * @return string|null
     */
    public function afterGetShippingAddress($subject, $result)
    {
        return $this->asText($result);
    }

    /**
     * @param mixed $address
     */
    private function asText($address): ?string
    {
        if ($address instanceof Address) {
            return trim((string) $this->renderer->format($address, 'oneline'));
        }

        return $address === null || is_scalar($address) ? ($address === null ? null : (string) $address) : null;
    }
}
