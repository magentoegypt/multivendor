<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MagentoEgypt\HubAppReturns\Model\Rma\MessagePoster;
use MagentoEgypt\HubAppReturns\Model\Rma\ReturnReader;

/**
 * Mutation.hmAddReturnMessage — the customer's reply on one of their open returns, with files if
 * any; notifies the seller and admin the way the website's reply form does (MessagePoster).
 */
class AddReturnMessage implements ResolverInterface
{
    public function __construct(
        private readonly MessagePoster $poster,
        private readonly ReturnReader $reader
    ) {
    }

    /**
     * @inheritDoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
    ) {
        $customerId = Caller::customerId($context);
        $input = $args['input'] ?? null;
        if (!is_array($input)) {
            throw new GraphQlInputException(__('"input" is required.'));
        }
        $requestId = (int) ($input['return_id'] ?? 0);

        $this->poster->post(
            $customerId,
            $requestId,
            (string) ($input['message'] ?? ''),
            $input['attachments'] ?? null
        );
        $rma = $this->reader->detail($customerId, $requestId, Caller::storeId($context));
        if ($rma === null) {
            throw new GraphQlNoSuchEntityException(__('This return doesn\'t exist.'));
        }

        return ['rma' => $rma];
    }
}
