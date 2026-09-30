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
use MagentoEgypt\HubAppReturns\Model\Rma\ReturnActions;
use MagentoEgypt\HubAppReturns\Model\Rma\ReturnReader;

/**
 * Mutation.hmEscalateReturn — the customer asks Hub Market to step in on one of their returns, with a
 * message and files if any, as the website's Escalate form does (ReturnActions::escalate); once a
 * return, never when it is cancelled.
 */
class EscalateReturn implements ResolverInterface
{
    public function __construct(
        private readonly ReturnActions $actions,
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

        $this->actions->escalate(
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
