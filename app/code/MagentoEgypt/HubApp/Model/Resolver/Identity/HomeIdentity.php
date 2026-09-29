<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Resolver\Identity;

use Magento\Framework\GraphQl\Query\Resolver\IdentityInterface;
use MagentoEgypt\HubApp\Model\Cache\Tags;
use MagentoEgypt\HubApp\Model\Home\HomeBuilder;
use MagentoEgypt\HubApp\Model\Resolver\Home\SectionProducts;

/**
 * HTTP cache tags of hmAppHome: hm_app_home, the tags the section providers
 * collected (cms_b_*, cat_c_*, hm_brand, hm_vendor, hm_app_catalog, …) and
 * cat_p / cat_p_<id> for every product a section shows, so saving one of those
 * products in admin refreshes the cached Home.
 */
class HomeIdentity implements IdentityInterface
{
    /**
     * @param array<mixed> $resolvedData
     * @return string[]
     */
    public function getIdentities(array $resolvedData): array
    {
        $tags = [Tags::APP_HOME];
        foreach ((array) ($resolvedData[HomeBuilder::TAGS_KEY] ?? []) as $tag) {
            if (is_string($tag) && $tag !== '') {
                $tags[] = $tag;
            }
        }

        $productIds = [];
        foreach ((array) ($resolvedData['sections'] ?? []) as $section) {
            if (is_array($section)) {
                foreach ((array) ($section[SectionProducts::IDS_KEY] ?? []) as $id) {
                    $productIds[] = (int) $id;
                }
            }
        }

        return array_values(array_unique(array_merge($tags, Tags::products($productIds))));
    }
}
