<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HeroBanner\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Actions extends Column
{
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array<string, mixed> $dataSource
     * @return array<string, mixed>
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            if (empty($item['banner_id'])) {
                continue;
            }

            $item[$this->getData('name')] = [
                'edit' => [
                    'href'  => $this->urlBuilder->getUrl('magentoegypt_herobanner/banner/edit', ['banner_id' => $item['banner_id']]),
                    'label' => __('Edit'),
                ],
                'delete' => [
                    'href'  => $this->urlBuilder->getUrl('magentoegypt_herobanner/banner/delete', ['banner_id' => $item['banner_id']]),
                    'label' => __('Delete'),
                    /*
                     * `post` makes the grid submit rather than follow the link —
                     * the delete controller is HttpPostActionInterface, so a plain
                     * href would 404 rather than delete.
                     */
                    'post'    => true,
                    'confirm' => [
                        'title'   => __('Delete banner'),
                        'message' => __('Delete "%1"? This cannot be undone.', $item['title'] ?? ''),
                    ],
                ],
            ];
        }

        return $dataSource;
    }
}
