<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\PushNotification\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Actions extends Column
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['notification_id'])) {
                    $item[$this->getData('name')] = [
                        'edit' => [
                            'href' => $this->urlBuilder->getUrl(
                                'pushnotification/notification/edit',
                                ['notification_id' => $item['notification_id']]
                            ),
                            'label' => __('Edit'),
                        ],
                        'send' => [
                            'href' => $this->urlBuilder->getUrl(
                                'pushnotification/notification/send',
                                ['notification_id' => $item['notification_id']]
                            ),
                            'label' => __('Send'),
                            'confirm' => [
                                'title' => __('Send Notification'),
                                'message' => __('Send this notification to its target audience?'),
                            ],
                        ],
                        'delete' => [
                            'href' => $this->urlBuilder->getUrl(
                                'pushnotification/notification/delete',
                                ['notification_id' => $item['notification_id']]
                            ),
                            'label' => __('Delete'),
                            'confirm' => [
                                'title' => __('Delete %1', $item['title'] ?? __('Notification')),
                                'message' => __('Are you sure you want to delete this notification?'),
                            ],
                        ],
                    ];
                }
            }
        }

        return $dataSource;
    }
}
