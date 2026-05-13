<?php

namespace Vnecoms\Quotation\Block\Adminhtml\Quote\Create\Customer;

/**
 * CMS block chooser for Wysiwyg CMS widget
 */
class Chooser extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    protected $_collectionFactory;
    
    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $collectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $collectionFactory,
        array $data = []
    ) {
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
        $this->setDefaultSort('block_identifier');
        $this->setDefaultDir('ASC');
        $this->setUseAjax(true);
        $this->setDefaultFilter(['chooser_is_active' => '1']);
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
        $sourceUrl = $this->getUrl('quotation/quote_create/chooser', ['uniq_id' => $uniqId]);

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
                var customerId = trElement.down("td").innerHTML.replace(/^\s+|\s+$/g,"");
                var customerEmail = trElement.down("td").next().innerHTML.trim();
                var customerFirstName = trElement.down("td").next().next().innerHTML.trim();
                var customerLastName = trElement.down("td").next().next().next().innerHTML.trim();
                jQuery("#customer_id").val(customerId);
                jQuery("#customer_email").val(customerEmail);
                jQuery("#customer_firstname").val(customerFirstName);
                jQuery("#customer_lastname").val(customerLastName);
            
                ' .
            $chooserJsObject .
            '.setElementValue(customerId);
                ' .
            $chooserJsObject .
            '.setElementLabel(customerId);
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
        $collection =$this->_collectionFactory->create();
        
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Prepare columns for Cms blocks grid
     *
     * @return \Magento\Backend\Block\Widget\Grid\Extended
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'chooser_id',
            ['header' => __('ID'), 'align' => 'right', 'index' => 'entity_id', 'width' => 50]
        );
        $this->addColumn('chooser_email', ['header' => __('Email'), 'align' => 'left', 'index' => 'email']);
        $this->addColumn('chooser_firstname', ['header' => __('First Name'), 'align' => 'left', 'index' => 'firstname']);
        $this->addColumn('chooser_lastname', ['header' => __('First Name'), 'align' => 'left', 'index' => 'lastname']);


        return parent::_prepareColumns();
    }

    /**
     * Get grid url
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('quotation/quote_create/chooser', ['_current' => true]);
    }
}
