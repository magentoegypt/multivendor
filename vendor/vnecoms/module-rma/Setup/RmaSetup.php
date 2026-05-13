<?php
/**
 * Customer resource setup model
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Setup;

use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Setup\Context;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory;

/**
 * @codeCoverageIgnore
 */
class RmaSetup extends EavSetup
{
    /**
     * EAV configuration
     *
     * @var Config
     */
    protected $eavConfig;

    /**
     * Init
     *
     * @param ModuleDataSetupInterface $setup
     * @param Context $context
     * @param CacheInterface $cache
     * @param CollectionFactory $attrGroupCollectionFactory
     * @param Config $eavConfig
     */
    public function __construct(
        ModuleDataSetupInterface $setup,
        Context $context,
        CacheInterface $cache,
        CollectionFactory $attrGroupCollectionFactory,
        Config $eavConfig
    ) {
        $this->eavConfig = $eavConfig;
        parent::__construct($setup, $context, $cache, $attrGroupCollectionFactory);
    }

    /**
     * Retrieve default entities: customer, customer_address
     *
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getDefaultEntities()
    {
        $entities = [
            'rma' => [
                'entity_model' => 'Vnecoms\RMA\Model\ResourceModel\Request',
                'attribute_model' => 'Vnecoms\RMA\Model\Attribute',
                'table' => 'ves_rma_request_entity',
                'increment_model' => 'Magento\Eav\Model\Entity\Increment\NumericValue',
                'additional_attribute_table' => 'ves_rma_request_eav_attribute',
                'entity_attribute_collection' => 'Vnecoms\RMA\Model\ResourceModel\Attribute\Collection',
                'attributes' => [
                    'website_id' => [
                        'type' => 'static',
                        'label' => 'Associate to Website',
                        'input' => 'select',
                        'source' => 'Magento\Customer\Model\Customer\Attribute\Source\Website',
                        'backend' => 'Magento\Customer\Model\Customer\Attribute\Backend\Website',
                        'sort_order' => 10,
                        'position' => 10,
                    ],
                    'package_opened' => [
                        'type' => 'static',
                        'label' => 'Title',
                        'input' => 'text',
                        'required' => true,
                        'sort_order' => 30,
                        'position' => 30
                    ],
                    'type' => [
                        'type' => 'static',
                        'label' => 'Type',
                        'input' => 'text',
                        'required' => true,
                        'sort_order' => 30,
                        'position' => 30
                    ],
                    'reason' => [
                        'type' => 'static',
                        'label' => 'Reason',
                        'input' => 'text',
                        'required' => true,
                        'sort_order' => 30,
                        'position' => 30
                    ],
                    'other_reason' => [
                        'type' => 'static',
                        'label' => 'Other Reason',
                        'input' => 'text',
                        'required' => true,
                        'sort_order' => 30,
                        'position' => 30
                    ],
                    'customer_email' => [
                        'type' => 'static',
                        'label' => 'Customer Email',
                        'input' => 'text',
                        'required' => true,
                        'sort_order' => 30,
                        'position' => 30
                    ],
                    'customer_name' => [
                        'type' => 'static',
                        'label' => 'Customer Name',
                        'input' => 'text',
                        'required' => true,
                        'sort_order' => 30,
                        'position' => 30
                    ],
                    'customer_id' => [
                        'type' => 'static',
                        'label' => 'Customer Id',
                        'input' => 'text',
                        'required' => true,
                        'sort_order' => 60,
                        'position' => 40,
                    ],
                    /*
                    'total_replies' => [
                        'type' => 'static',
                        'label' => 'Total Reply',
                        'input' => 'text',
                        'required' => true,
                        'sort_order' => 30,
                        'position' => 30
                    ],
                    */
                    'order_incremental_id' => [
                        'type' => 'static',
                        'label' => 'Order Increment Id',
                        'input' => 'text',
                        'required' => true,
                        'sort_order' => 60,
                        'position' => 40,
                    ],
                    'ip_address' => [
                        'type' => 'static',
                        'label' => 'Ip Address',
                        'input' => 'text',
                        'required' => false,
                        'sort_order' => 86,
                        'visible' => false,
                        'system' => false,
                    ],
                    'status' => [
                        'type' => 'static',
                        'label' => 'Status',
                        'input' => 'text',
                        'required' => true,
                        'sort_order' => 60,
                        'position' => 50,
                    ],
                    'state' => [
                        'type' => 'static',
                        'label' => 'State',
                        'input' => 'text',
                        'required' => true,
                        'sort_order' => 60,
                        'position' => 50,
                    ],
                    'note' => [
                        'type' => 'text',
                        'label' => 'Note',
                        'input' => 'text',
                        'required' => false,
                        'sort_order' => 60,
                        'position' => 50,
                    ],
                    'addition_data' => [
                        'type' => 'text',
                        'label' => 'addition data',
                        'input' => 'text',
                        'required' => false,
                        'sort_order' => 60,
                        'position' => 50,
                    ],
                    'tracking_code' => [
                        'type' => 'varchar',
                        'label' => 'Tracking Code',
                        'input' => 'text',
                        'required' => false,
                        'sort_order' => 60,
                        'position' => 50,
                    ],
                    'created_at' => [
                        'type' => 'static',
                        'label' => 'Created At',
                        'input' => 'date',
                        'required' => false,
                        'sort_order' => 86,
                        'visible' => false,
                        'system' => false,
                    ],
                    'updated_at' =>[
                        'type' => 'static',
                        'label' => 'Updated At',
                        'input' => 'date',
                        'required' => false,
                        'sort_order' => 87,
                        'visible' => false,
                        'system' => false,
                    ],
                ],
            ]
        ];
        return $entities;
    }

    /**
     * Gets EAV configuration
     *
     * @return Config
     */
    public function getEavConfig()
    {
        return $this->eavConfig;
    }
}
