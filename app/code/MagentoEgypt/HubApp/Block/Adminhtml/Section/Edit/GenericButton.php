<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Block\Adminhtml\Section\Edit;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;

/**
 * Shared bits of the section form's buttons.
 */
class GenericButton
{
    public function __construct(
        protected readonly UrlInterface $urlBuilder,
        protected readonly RequestInterface $request
    ) {
    }

    public function getSectionId(): int
    {
        return (int) $this->request->getParam('section_id');
    }

    /**
     * @param array<string, mixed> $params
     */
    public function getUrl(string $route = '', array $params = []): string
    {
        return $this->urlBuilder->getUrl($route, $params);
    }
}
