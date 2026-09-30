<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use MagentoEgypt\HubApp\Model\Home\Section;

/**
 * Who sees a section. Targeting only: the app says which Home it wants
 * (hmAppHome audience GUEST / CUSTOMER), nothing here is access control.
 */
class Audience implements OptionSourceInterface
{
    /**
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => Section::AUDIENCE_ALL, 'label' => __('Everyone')],
            ['value' => Section::AUDIENCE_GUEST, 'label' => __('Guests (not signed in)')],
            ['value' => Section::AUDIENCE_CUSTOMER, 'label' => __('Signed-in customers')],
        ];
    }
}
