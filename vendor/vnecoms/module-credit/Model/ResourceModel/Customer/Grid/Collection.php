<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\Credit\Model\ResourceModel\Customer\Grid;

use Magento\Customer\Model\ResourceModel\Customer;
use Magento\Customer\Ui\Component\DataProvider\Document;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface as Logger;

/**
 * Collection to present grid of customers on admin area
 */
class Collection extends \Magento\Customer\Model\ResourceModel\Grid\Collection
{
    /**
     * @inheritdoc
     */
    protected function _initSelect()
    {
        parent::_initSelect();

        $this->getSelect()->joinLeft(['store_credit'=>$this->getTable('ves_store_credit')], 'store_credit.customer_id= main_table.entity_id', ['credit']);

        return $this;
    }
}
