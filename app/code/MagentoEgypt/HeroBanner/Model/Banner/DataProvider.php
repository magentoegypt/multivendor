<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HeroBanner\Model\Banner;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use MagentoEgypt\HeroBanner\Model\ResourceModel\Banner\CollectionFactory;

class DataProvider extends AbstractDataProvider
{
    /** @var array<int, array<string, mixed>>|null */
    private ?array $loaded = null;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getData(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        $this->loaded = [];

        foreach ($this->collection->getItems() as $banner) {
            $row = $banner->getData();
            $id  = (int) $banner->getId();

            /*
             * The UI file-uploader field expects an array of {name, url, size},
             * not the bare path the column holds. Without this the form renders
             * an empty uploader for a record that HAS an image, and saving then
             * silently clears it.
             */
            $path = trim((string) ($row['image'] ?? ''));
            $row['image'] = $path === '' ? [] : [[
                'name' => basename($path),
                'url'  => $this->mediaUrl($path),
            ]];

            $this->loaded[$id] = $row;
        }

        /*
         * A save that failed validation comes back through here — restore what
         * the user typed rather than making them retype it.
         */
        $persisted = $this->dataPersistor->get('magentoegypt_hero_banner');
        if ($persisted) {
            $id = (int) ($persisted['banner_id'] ?? 0);
            $this->loaded[$id] = $persisted;
            $this->dataPersistor->clear('magentoegypt_hero_banner');
        }

        return $this->loaded;
    }

    private function mediaUrl(string $path): string
    {
        try {
            $base = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        } catch (\Throwable $e) {
            return $path;
        }

        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }
}
