<?php
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Plugin\Framework;

use Magento\Framework\App\Http\Context;

/**
 * Hub Market — TC47 (14zb93nupm4): no cookie-less request was ever served from cache.
 *
 * Vnecoms\Vendors\Plugin\CustomerSessionContext copies the session customer's name
 * into the HTTP context on every request. For a guest the customer model's getName()
 * is " " (the joined empty name parts), which Context::getData() keeps because a
 * space is truthy. So every guest's vary string was hash({"customer_name":" "})
 * instead of null, and HttpPlugin::beforeSendResponse() marks any response
 * UNCACHEABLE when that differs from the visitor's X-Magento-Vary cookie — which a
 * first visit, a crawler, CloudFront and the synthetic monitor never have. Proven by
 * matching the live X-Magento-Vary against sha256('{"customer_name":" "}|<key>').
 *
 * A whitespace-only string carries no context, so store it as null (dropped by
 * getData()). Real names, IDs and every non-string value pass through untouched,
 * so logged-in customers keep their own vary exactly as before.
 */
class HttpContextBlankValue
{
    /**
     * @param Context $subject
     * @param string $name
     * @param mixed $value
     * @param mixed $default
     * @return array
     */
    public function beforeSetValue(Context $subject, $name, $value, $default): array
    {
        if (is_string($value) && trim($value) === '') {
            $value = null;
        }
        return [$name, $value, $default];
    }
}
