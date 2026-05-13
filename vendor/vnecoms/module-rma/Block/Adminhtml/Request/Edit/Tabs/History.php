<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Related products admin grid
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Vnecoms\RMA\Block\Adminhtml\Request\Edit\Tabs;

use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Backend\Block\Widget\Grid\Extended;

class History extends Extended
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Vnecoms\RMA\Model\RequestFactory
     */
    protected $_requestFactory;
    /**
     * @var \Vnecoms\RMA\Helper\Config
     */
    protected $_requestHelper;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Vnecoms\RMA\Model\RequestFactory $productFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Vnecoms\RMA\Helper\Config $requestHelper,
        \Vnecoms\RMA\Model\RequestFactory $requestFactory,
        \Magento\Framework\Registry $coreRegistry,
        array $data = []
    ) {
        $this->_requestHelper = $requestHelper;
        $this->_requestFactory = $requestFactory;
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Set grid params
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('customer_rma');
        $this->setDefaultSort('created_at');
        $this->setUseAjax(true);
    }

    /**
     * Retrieve currently edited request model
     *
     * @return array|null
     */
    public function getRequestRma()
    {
        return $this->_coreRegistry->registry('current_request');
    }

    /**
     * Prepare collection
     *
     * @return Extended
     */
    protected function _prepareCollection()
    {
        $customer_email = $this->getRequestRma()->getCustomerEmail();
        $collection = $this->_requestFactory->create()->getCollection()
            ->addAttributeToSelect('*');
        $collection->addAttributeToFilter("customer_email", $customer_email);
        $collection->addAttributeToFilter("entity_id", ["neq"=>$this->getRequestRma()->getId()]);

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Add columns to grid
     *
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareColumns()
    {
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();

        $this->addColumn(
            'increment_id',
            [
                'header' => __('Increment ID'),
                'sortable' => true,
                'index' => 'increment_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );

        $this->addColumn(
            'order_incremental_id',
            [
                'header' => __('Order ID'),
                'sortable' => true,
                'index' => 'order_incremental_id',
                'header_css_class' => 'col-order-id',
                'column_css_class' => 'col-order-id'
            ]
        );

        $type = $object_manager->get('\Vnecoms\RMA\Ui\Component\Grid\Request\Type');
        $options  = $type->getOptionArray();
        $this->addColumn(
            'type',
            [
                'header' => __('Type'),
                'index' => 'type',
                'type' => 'options',
                'options' => $options,
                'header_css_class' => 'col-type',
                'column_css_class' => 'col-type',
                'renderer' => '\Vnecoms\RMA\Block\Adminhtml\Widget\Columns\Type',
            ]
        );

        $this->addColumn(
            'reason',
            [
                'header' => __('Reason'),
                'sortable' => false,
                'filter' => false,
                'index' => 'reason',
                'header_css_class' => 'col-reason',
                'column_css_class' => 'col-reason',
                'renderer' => '\Vnecoms\RMA\Block\Adminhtml\Widget\Columns\Reason',
            ]
        );


        $this->addColumn(
            'created_at',
            [
                'header' => __('Created Time'),
                'index' => 'created_at',
                'header_css_class' => 'col-created',
                'column_css_class' => 'col-created',
                'type' => 'datetime'
            ]
        );

        $this->addColumn(
            'updated_at',
            [
                'header' => __('Updated Time'),
                'index' => 'updated_at',
                'header_css_class' => 'col-updated',
                'column_css_class' => 'col-updated',
                'type' => 'datetime'
            ]
        );
        $type = $object_manager->get('\Vnecoms\RMA\Ui\Component\Grid\Request\Status');
        $options  = $type->getOptionArrayGrid();
        $this->addColumn(
            'status',
            [
                'header' => __('Status'),
                'index' => 'status',
                'type' => 'options',
                'options' => $options,
                'header_css_class' => 'col-status',
                'column_css_class' => 'col-status',
                'renderer' => '\Vnecoms\RMA\Block\Adminhtml\Widget\Columns\Status',
            ]
        );

        $this->addColumn(
            'action',
            [
                'header' => __('Action'),
                'type' => 'action',
                'getter'    => 'getId',
                'actions' => [
                    [
                        'caption' => __('View'),
                        'url' => [
                            'base' => 'rma/request/view',
                        ],
                        'field' => 'request_id',
                        'target' => '_blank'
                    ],
                ],
                'filter' => false,
                'sortable' => false
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * Rerieve grid URL
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getData(
            'grid_url'
        ) ? $this->getData(
            'grid_url'
        ) : $this->getUrl(
            'vrma/request/grid',
            ['_current' => true]
        );
    }
}
