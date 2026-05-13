<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\SmsGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Vnecoms\SmsGraphQl\Model\Sms\SaveMobile;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;

/**
 * Products field resolver, used for GraphQL request processing.
 */
class SaveMobileToCustomer implements ResolverInterface
{
    /**
     * @var GetList
     */
    private $model;

    /**
     * SaveMobileToCustomer constructor.
     * @param SaveMobile $saveMobile
     */
    public function __construct(
        SaveMobile $saveMobile
    ) {
        $this->model = $saveMobile;
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
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }

        $result = $this->model->execute($args, $context);
        return $result;
    }
}
