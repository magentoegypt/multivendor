<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HeroBanner\Block\Adminhtml\Banner\Edit;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class BackButton implements ButtonProviderInterface
{
    public function __construct(private readonly UrlInterface $urlBuilder)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function getButtonData(): array
    {
        return [
            'label'      => __('Back'),
            'on_click'   => sprintf("location.href = '%s';", $this->urlBuilder->getUrl('*/*/')),
            'class'      => 'back',
            'sort_order' => 10,
        ];
    }
}
