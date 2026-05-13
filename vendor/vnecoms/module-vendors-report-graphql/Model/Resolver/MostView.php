<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\VendorsReportGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Vnecoms\VendorsReportGraphQl\Model\Report\GetListMostView;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;

/**
 * Products field resolver, used for GraphQL request processing.
 */
class MostView implements ResolverInterface
{
    /**
     * @var GetListMostView
     */
    private $getListMostView;

    /**
     * MostView constructor.
     * @param GetListMostView $getListMostView
     */
    public function __construct(
        GetListMostView $getListMostView
    ) {
        $this->getListMostView = $getListMostView;
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
            throw new GraphQlAuthorizationException(__('The current vendor isn\'t authorized.'));
        }

        if ($args['limit'] < 1) {
            throw new GraphQlInputException(__('limit value must be greater than 0.'));
        }

        $searchResult = $this->getListMostView->execute($args, $info, $context);
        return [
            'items' => $searchResult
        ];
    }
}
