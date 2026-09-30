<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Source;

use Magento\Cms\Model\ResourceModel\Block\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * CMS blocks by IDENTIFIER, for the CMS-backed section types.
 *
 * By identifier rather than id: a block exists once per store view with the
 * same identifier (hm_home_promos is one row for Arabic and one for English),
 * and the section must pick the store view's own copy at read time, as
 * cmsBlocks(identifiers:) does.
 */
class CmsBlock implements OptionSourceInterface
{
    /** @var array<int, array{value: string, label: string}>|null */
    private ?array $options = null;

    public function __construct(private readonly CollectionFactory $collectionFactory)
    {
    }

    /**
     * @return array<int, array{value: string, label: string|\Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        if ($this->options !== null) {
            return $this->options;
        }

        $byIdentifier = [];
        $collection = $this->collectionFactory->create();
        $collection->addFieldToSelect(['identifier', 'title', 'is_active'])
            ->setOrder('identifier', 'ASC');
        foreach ($collection as $block) {
            $identifier = (string) $block->getData('identifier');
            if ($identifier === '' || isset($byIdentifier[$identifier])) {
                continue;
            }
            $title = trim((string) $block->getData('title'));
            $label = $title !== '' && $title !== $identifier ? $title . ' (' . $identifier . ')' : $identifier;
            if (!(int) $block->getData('is_active')) {
                $label .= ' — ' . __('disabled');
            }
            $byIdentifier[$identifier] = ['value' => $identifier, 'label' => $label];
        }

        $this->options = array_merge(
            [['value' => '', 'label' => (string) __('Default for the section type')]],
            array_values($byIdentifier)
        );

        return $this->options;
    }
}
