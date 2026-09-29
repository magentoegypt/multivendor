<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MagentoEgypt\HubAppReturns\Model\Rma\ReturnCreator;
use MagentoEgypt\HubAppReturns\Model\Rma\ReturnReader;

/**
 * Mutation.hmCreateReturn — file a return for one seller's lines of one of the customer's orders,
 * by the website's rules (see EligibilityService) and through the website's own save path
 * (ReturnCreator), so admin, the seller and the e-mails see an ordinary website return.
 */
class CreateReturn implements ResolverInterface
{
    public function __construct(
        private readonly ReturnCreator $creator,
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
        $storeId = Caller::storeId($context);
        $input = $args['input'] ?? null;
        if (!is_array($input)) {
            throw new GraphQlInputException(__('"input" is required.'));
        }

        $requestId = $this->creator->create($customerId, $storeId, $input);
        $rma = $this->reader->detail($customerId, $requestId, $storeId);
        if ($rma === null) {
            throw new GraphQlInputException(__('We couldn\'t file the return. Please try again.'));
        }

        return ['rma' => $rma];
    }
}
