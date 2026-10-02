<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Resolver;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MagentoEgypt\HubApp\Model\Product\PersonalPicks;

/**
 * hmPickedForYou: the Picked For You products for one shopper (see Model\Product\PersonalPicks).
 *
 * Not cached (the schema says so): the answer depends on the user_token. The app keeps showing the Home
 * section's cached top-rated products and swaps in these when `personalized` is true; Refresh pages through
 * them (up to 16).
 *
 * Optional dependency with an ObjectManager fallback: added without a di:compile.
 */
class PickedForYou implements ResolverInterface
{
    private readonly PersonalPicks $picks;

    public function __construct(?PersonalPicks $picks = null)
    {
        $this->picks = $picks ?? ObjectManager::getInstance()->get(PersonalPicks::class);
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
        $token = trim((string) ($args['user_token'] ?? ''));
        if (!PersonalPicks::isValidToken($token)) {
            throw new GraphQlInputException(__('user_token must be the Algolia user token the app sends events with.'));
        }
        [$pageSize, $currentPage] = Paging::args($args, 4, PersonalPicks::MAX_PICKS);
        $storeId = (int) $context->getExtensionAttributes()->getStore()->getId();

        $result = $this->picks->forShopper($token, $storeId);
        $ids = $result['ids'];

        return [
            'total_count' => count($ids),
            'page_info' => Paging::info(count($ids), $pageSize, $currentPage),
            'countdown_ends_at' => null,
            'personalized' => $result['personalized'],
            ProductPageItems::IDS_KEY => Paging::slice($ids, $pageSize, $currentPage),
        ];
    }
}
