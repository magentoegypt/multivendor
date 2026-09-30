<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Home\Provider;

use MagentoEgypt\HubApp\Api\Home\SectionProviderInterface;
use MagentoEgypt\HubApp\Model\Home\SectionContext;
use MagentoEgypt\HubApp\Model\Home\SectionResult;
use MagentoEgypt\HubApp\Model\Source\SectionType;

/**
 * ACTIVE_ORDER (SectionType::PLACEMENT_TYPES): where on the Home the app draws
 * the signed-in customer's open order.
 *
 * The section carries no content. The built Home is shared by every viewer of
 * the store view and audience (app cache and HTTP cache), so it can never hold
 * anyone's order: the app reads the order itself (core customer.orders) and
 * draws nothing for a guest or when no recent order is open. No product data,
 * no tags beyond the section's own (the builder adds hm_app_home_<id>).
 */
class PlacementProvider implements SectionProviderInterface
{
    public function provide(SectionContext $context): ?SectionResult
    {
        return in_array($context->getType(), SectionType::PLACEMENT_TYPES, true)
            ? SectionResult::placement()
            : null;
    }
}
