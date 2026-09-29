<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Section;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use MagentoEgypt\HubApp\Model\ResourceModel\Section\CollectionFactory;

/**
 * Data of the section form. The UI form filters the collection to the
 * requested section_id before getData() runs.
 *
 * Dates go out as stored (UTC); the date field (showsTime, storeTimeZone set
 * by the Date data type) displays them in the store's timezone and posts UTC
 * back. The `options` JSON is unfolded into its textareas by FormDataMapper.
 */
class DataProvider extends AbstractDataProvider
{
    public const PERSISTOR_KEY = 'magentoegypt_hubapp_home_section';

    /** @var array<int|string, array<string, mixed>>|null */
    private ?array $loaded = null;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param array<string, mixed> $meta
     * @param array<string, mixed> $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly FormDataMapper $mapper,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    public function getData(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        $this->loaded = [];
        foreach ($this->collection->getItems() as $section) {
            $this->loaded[(int) $section->getId()] = $this->mapper->toForm($section->getData());
        }

        //  A save that failed validation comes back here: restore what was typed.
        $persisted = $this->dataPersistor->get(self::PERSISTOR_KEY);
        if (is_array($persisted) && $persisted) {
            $id = (int) ($persisted['section_id'] ?? 0);
            //  A new section is looked up under the empty id by Ui\Component\Form.
            $key = $id > 0 ? $id : '';
            $this->loaded[$key] = array_merge($this->loaded[$key] ?? [], $persisted);
            $this->dataPersistor->clear(self::PERSISTOR_KEY);
        }

        return $this->loaded;
    }
}
