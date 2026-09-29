<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Home\Provider;

use Magento\CmsGraphQl\Model\Resolver\DataProvider\Block as BlockProvider;
use Magento\Framework\Exception\NoSuchEntityException;
use MagentoEgypt\HubApp\Api\Home\SectionProviderInterface;
use MagentoEgypt\HubApp\Model\Cache\Tags;
use MagentoEgypt\HubApp\Model\Home\SectionContext;
use MagentoEgypt\HubApp\Model\Home\SectionResult;
use MagentoEgypt\HubApp\Model\Source\SectionType;

/**
 * DELIVERY_STRIP, CMS_PROMOS, TRUST_ROW, CMS_BLOCK: one CMS block.
 *
 * Read exactly as cmsBlocks(identifiers:) reads it (CmsGraphQl's Block data
 * provider): the store view's active copy of the identifier, content rendered
 * through the widget filter. A missing or disabled block omits the section.
 * Tagged cms_b_<id> and cms_b_<identifier>, which core purges when the block
 * is saved.
 */
class CmsBlockProvider implements SectionProviderInterface
{
    public function __construct(private readonly BlockProvider $blockProvider)
    {
    }

    public function provide(SectionContext $context): ?SectionResult
    {
        $identifier = $context->getCmsIdentifier() ?? (SectionType::CMS_DEFAULTS[$context->getType()] ?? null);
        if ($identifier === null || $identifier === '') {
            return null;
        }

        try {
            $block = $this->blockProvider->getBlockByIdentifier($identifier, $context->getStoreId());
        } catch (NoSuchEntityException $e) {
            return null;
        }

        $content = trim((string) ($block['content'] ?? ''));
        if ($content === '') {
            return null;
        }
        $title = trim((string) ($block['title'] ?? ''));

        return SectionResult::create()
            ->withField('cms_block', [
                'identifier' => (string) $block['identifier'],
                'title' => $title !== '' ? $title : null,
                'content' => $content,
            ])
            ->withTags(array_merge(
                [Tags::CMS_BLOCK],
                Tags::cmsBlock((int) $block['block_id'], (string) $block['identifier'])
            ));
    }
}
