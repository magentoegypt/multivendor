<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Webapi;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Webapi\Rest\Request as ApiRequest;
use Magento\Framework\Webapi\Rest\Request\ParamOverriderInterface;
use Magento\Quote\Model\Webapi\ParamOverriderCartId as QuoteParamOverriderCartId;

/**
 * Replaces a "%amasty_checkout_cart_id%" value with the current authenticated customer's cart or negotiable quote
 */
class ParamOverriderCartId implements ParamOverriderInterface
{
    /**
     * @var QuoteParamOverriderCartId
     */
    private $paramOverriderCartId;

    /**
     * @var ApiRequest
     */
    private $apiRequest;

    public function __construct(
        QuoteParamOverriderCartId $paramOverriderCartId,
        ApiRequest $apiRequest
    ) {
        $this->paramOverriderCartId = $paramOverriderCartId;
        $this->apiRequest = $apiRequest;
    }

    public function getOverriddenValue()
    {
        try {
            $overriddenValue = $this->paramOverriderCartId->getOverriddenValue();
            $cartId = $this->apiRequest->getRequestData()['cartId'] ?? null;
            if ($overriddenValue && $cartId && $overriddenValue != $cartId) {
                return $cartId;
            }

            return $overriddenValue;
        } catch (NoSuchEntityException $e) {
            return $this->apiRequest->getRequestData()['cartId'] ?? null;
        }
    }
}
