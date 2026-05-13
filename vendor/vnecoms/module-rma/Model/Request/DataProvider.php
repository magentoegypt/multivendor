<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/23/2016
 * Time: 11:13 AM
 */
namespace Vnecoms\RMA\Model\Request;

use Vnecoms\RMA\Model\ResourceModel\Request\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;

/**
 * Class DataProvider
 */
class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var \Vnecoms\RMA\Model\ResourceModel\Reponse\Collection
     */
    protected $collection;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * DataProvider constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $gatewayCollectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $reponseCollectionFactory,
        DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $reponseCollectionFactory->create();
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
        /** @var \Vnecoms\RMA\Model\Reponse $gateway */
        foreach ($items as $gateway) {
            $this->loadedData[$gateway->getId()] = $gateway->getData();
        }

        $data = $this->dataPersistor->get('rma_request');
        if (!empty($data)) {
            $reponse = $this->collection->getNewEmptyItem();
            $reponse->setData($data);
            $this->loadedData[$reponse->getId()] = $reponse->getData();
            $this->dataPersistor->clear('rma_request');
        }

        return $this->loadedData;
    }
}
