<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/23/2016
 * Time: 11:13 AM
 */
namespace Vnecoms\VendorsRMA\Model\Request;

use Vnecoms\RMA\Model\ResourceModel\Request\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;

/**
 * Class DataProvider
 */
class DataProvider extends \Vnecoms\RMA\Model\Request\DataProvider
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
        parent::__construct($name, $primaryFieldName, $requestFieldName, $reponseCollectionFactory ,$dataPersistor ,$meta, $data);
        /*Join with vendor table.*/
        $this->collection->joinTable($this->collection->getTable('ves_vendor_entity'), "entity_id=vendor_id",['vendor_identifier'=>'vendor_id'],null,'left');
    }

}
