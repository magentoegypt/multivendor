<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\Vendors\Block\Adminhtml\Vendor;

/**
 * CMS block chooser for Wysiwyg CMS widget
 */
class Chooser extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Vnecoms\Vendors\Model\VendorFactory
     */
    protected $_vendorFactory;

    /**
     * @var \Vnecoms\Vendors\Model\ResourceModel\Vendor\CollectionFactory
     */
    protected $_collectionFactory;


    /**
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Vnecoms\Vendors\Model\VendorFactory $productFactory
     * @param \Vnecoms\Vendors\Model\ResourceModel\Vendor\CollectionFactory $collectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Vnecoms\Vendors\Model\VendorFactory $productFactory,
        \Vnecoms\Vendors\Model\ResourceModel\Customer\CollectionFactory $collectionFactory,
        array $data = []
    ) {
        $this->_productFactory = $productFactory;
        $this->_collectionFactory = $collectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Block construction, prepare grid params
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('ASC');
        $this->setUseAjax(true);
    }

    /**
     * Prepare chooser element HTML
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element Form Element
     * @return \Magento\Framework\Data\Form\Element\AbstractElement
     */
    public function prepareElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $uniqId = $this->mathRandom->getUniqueHash($element->getId());
        $sourceUrl = $this->getUrl('vendors/customer/chooser', ['uniq_id' => $uniqId]);
        $chooser = $this->getLayout()->createBlock(
            'Magento\Widget\Block\Adminhtml\Widget\Chooser'
        )->setElement(
            $element
        )->setConfig(
            $this->getConfig()
        )->setFieldsetId(
            $this->getFieldsetId()
        )->setSourceUrl(
            $sourceUrl
        )->setUniqId(
            $uniqId
        );

        $element->setData('after_element_html', $chooser->toHtml());
        return $element;
    }

    /**
     * Grid Row JS Callback
     *
     * @return string
     */
    public function getRowClickCallback()
    {
        $chooserJsObject = $this->getId();
        $js = '
            function (grid, event) {
                var trElement = Event.findElement(event, "tr");
                var customerEntityId = trElement.down("td").innerHTML.replace(/^\s+|\s+$/g,"");
                var entityId = trElement.down("td").next().innerHTML;
                ' .
            $chooserJsObject .
            '.setElementValue(customerEntityId);
                ' .
            $chooserJsObject .
            '.setElementLabel(entityId);
                ' .
            $chooserJsObject .
            '.close();
            }
        ';
        return $js;
    }

    /**
     * Prepare Cms static blocks collection
     *
     * @return \Magento\Backend\Block\Widget\Grid\Extended
     */
    protected function _prepareCollection()
    {
        $collection = $this->_collectionFactory->create();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Get Option Array
     *
     * @param array $options
     * @return multitype:unknown
     */
    public function getOptionArray($options = []){
        $arr = [];
        foreach($options as $option){
            $arr[$option['value']] = $option['label'];
        }

        return $arr;
    }
    /**
     * Prepare columns for Cms blocks grid
     *
     * @return \Magento\Backend\Block\Widget\Grid\Extended
     */
    protected function _prepareColumns()
    {
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $this->addColumn('entity_id',[
                'header' => __('Customer Id'),
                'align' => 'right',
                'type' => 'number',
                'index' => 'entity_id',
                'width' => 50
            ]
        );

//        $this->addColumn('customer_id', ['header' => __('Customer Id'), 'align' => 'left', 'index' => 'entity_id']);
        $this->addColumn('lastname', ['header' => __('Last Name'), 'align' => 'left', 'index' => 'lastname']);
        $this->addColumn('firstname', ['header' => __('First Name'), 'align' => 'left', 'index' => 'firstname']);
        $this->addColumn('email', ['header' => __('Email'), 'align' => 'left', 'index' => 'email']);

        return parent::_prepareColumns();
    }

    /**
     * Get grid url
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('vendors/customer/chooser', ['_current' => true]);
    }
}
