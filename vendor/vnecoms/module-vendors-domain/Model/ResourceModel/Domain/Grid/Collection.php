<?php
namespace Vnecoms\VendorsDomain\Model\ResourceModel\Domain\Grid;

use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Psr\Log\LoggerInterface as Logger;


class Collection extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
{
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager
    ) {
        $mainTable = 'ves_vendor_domain';
        $resourceModel = 'Vnecoms\VendorsDomain\Model\ResourceModel\Domain';
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
    }


    protected function _construct()
    {
        parent::_construct();
        $fields = [
            'status',
            'created_at',
            'vendor_id'
        ];
        foreach($fields as $field){
            $this->addFilterToMap(
                $field,
                'main_table.'.$field
            );
        }

        $this->addFilterToMap(
            "vendor",
            'vendor.vendor_id'
        );
    }

    /**
     * Init collection select
     *
     * @return $this
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->join(
            ['vendor'=>$this->getTable('ves_vendor_entity')],
            'vendor.entity_id = main_table.vendor_id',
            ['vendor' => 'vendor_id'],
            null,
            'left'
        );
        return $this;
    }
}
