<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppBundle\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MagentoEgypt\HubAppBundle\Model\Pricing\PackagePricer;

/**
 * Query.hmBundleQuote(sku, quantity, selections): the price of one package, the
 * way the cart will charge it (see PackagePricer).
 *
 * Public and cacheable: the selections travel in the document, so the same
 * package is the same GET. Priced for the caller's customer group (guests
 * without a token, like the storefront's cached product pages). A package the
 * cart would refuse is not an error: it comes back with available false and
 * the reason, so the page can keep its own figure and say why.
 */
class BundleQuote implements ResolverInterface
{
    /** Magento\Customer\Model\Group::NOT_LOGGED_IN_ID */
    private const GUEST_GROUP = 0;

    public function __construct(private readonly PackagePricer $pricer)
    {
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        $sku = trim((string) ($args['sku'] ?? ''));
        if ($sku === '') {
            throw new GraphQlInputException(__('Required parameter "sku" is missing'));
        }
        $quantity = isset($args['quantity']) ? (float) $args['quantity'] : 1.0;

        $extension = $context->getExtensionAttributes();
        $groupId = $extension->getCustomerGroupId();

        return $this->pricer->quote(
            $sku,
            $quantity,
            array_values((array) ($args['selections'] ?? [])),
            $extension->getStore(),
            $groupId !== null ? (int) $groupId : self::GUEST_GROUP
        );
    }
}
