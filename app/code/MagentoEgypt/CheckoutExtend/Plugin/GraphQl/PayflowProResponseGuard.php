<?php
declare(strict_types=1);

namespace MagentoEgypt\CheckoutExtend\Plugin\GraphQl;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\PaypalGraphQl\Model\Resolver\PayflowProResponse;
use Magento\Store\Model\ScopeInterface;

/**
 * Hub Market — TC45 (2026-09-24): handlePayflowProResponse is reachable on /graphql
 * although Payflow Pro / Payments Pro are disabled. Bots call it with a made-up cart;
 * the resolver then throws a raw InvalidArgumentException ("Variable must contain
 * instance of \Quote\Payment.") that is logged as a server error.
 *
 * With neither method active there is nothing to handle: answer with a normal
 * client input error before the resolver runs.
 */
class PayflowProResponseGuard
{
    private const METHODS = ['payflowpro', 'paypal_payment_pro'];

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundResolve(
        PayflowProResponse $subject,
        callable $proceed,
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
    ) {
        $storeId = null;
        if (is_object($context) && method_exists($context, 'getExtensionAttributes')) {
            $store = $context->getExtensionAttributes()->getStore();
            $storeId = $store ? (int)$store->getId() : null;
        }
        foreach (self::METHODS as $method) {
            if ($this->scopeConfig->isSetFlag('payment/' . $method . '/active', ScopeInterface::SCOPE_STORE, $storeId)) {
                return $proceed($field, $context, $info, $value, $args);
            }
        }
        throw new GraphQlInputException(__('Payflow Pro payments are not available.'));
    }
}
