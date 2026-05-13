<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\PushNotification\Ui\DataProvider\Notification\Form;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use MagentoEgypt\PushNotification\Model\ResourceModel\Notification\CollectionFactory;

class DataProvider extends AbstractDataProvider
{
    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();
        $this->loadedData = [];

        foreach ($items as $item) {
            $data = $item->getData();

            if (!empty($data['image'])) {
                $data['image'] = [
                    [
                        'name' => $data['image'],
                        'url'  => $this->getImageUrl($data['image']),
                    ],
                ];
            }

            $this->loadedData[$item->getId()] = $data;
        }

        $data = $this->dataPersistor->get('magentoegypt_push_notification');
        if (!empty($data)) {
            $item = $this->collection->getNewEmptyItem();
            $item->setData($data);
            $this->loadedData[$item->getId() ?: null] = $item->getData();
            $this->dataPersistor->clear('magentoegypt_push_notification');
        }

        return $this->loadedData;
    }

    /**
     * Build URL for the saved image, when available.
     *
     * @param string $file
     * @return string
     */
    protected function getImageUrl($file)
    {
        return $file;
    }
}
