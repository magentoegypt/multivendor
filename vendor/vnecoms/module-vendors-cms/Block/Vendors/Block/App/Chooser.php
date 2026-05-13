<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 */

namespace Vnecoms\VendorsCms\Block\Vendors\Block\App;

/**
 * CMS block chooser for app Vendors CMS widget.
 */
class Chooser extends \Vnecoms\Vendors\Block\Vendors\Widget\Grid\Extended
{
    /**
     * @var \Vnecoms\VendorsCms\Model\BlockFactory
     */
    protected $_blockFactory;

    /**
     * @var \Vnecoms\VendorsCms\Model\ResourceModel\Block\CollectionFactory
     */
    protected $_collectionFactory;

    protected $url;
    /**
     * @param \Magento\Backend\Block\Template\Context                         $context
     * @param \Magento\Backend\Helper\Data                                    $backendHelper
     * @param \Vnecoms\VendorsCms\Model\BlockFactory                          $blockFactory
     * @param \Vnecoms\VendorsCms\Model\ResourceModel\Block\CollectionFactory $collectionFactory
     * @param array                                                           $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Vnecoms\VendorsCms\Model\BlockFactory $blockFactory,
        \Vnecoms\VendorsCms\Model\ResourceModel\Block\CollectionFactory $collectionFactory,
        \Vnecoms\Vendors\Model\UrlInterface $url,
        array $data = []
    ) {
        $this->_blockFactory = $blockFactory;
        $this->_collectionFactory = $collectionFactory;
        parent::__construct($context, $backendHelper, $data);
        $this->url = $url;
    }

    /**
     * Block construction, prepare grid params.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setDefaultSort('block_identifier');
        $this->setDefaultDir('ASC');
        $this->setUseAjax(true);
        $this->setId($this->getRequest()->getParam('uniq_id'));
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
        $sourceUrl = $this->getUrl('cms/block_app/grid', ['uniq_id' => $uniqId]);

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

        if ($element->getValue()) {
            $block = $this->_blockFactory->create()->load((int)$element->getValue());
            if ($block->getId()) {
                $chooser->setLabel($block->getTitle());
            }
        }

        $element->setData('after_element_html', $chooser->toHtml());
        return $element;
    }

    /**
     * Grid Row JS Callback.
     *
     * @return string
     */
    public function getRowClickCallback()
    {
        $chooserJsObject = $this->getId();
        $js = '
            function (grid, event) {
                var trElement = Event.findElement(event, "tr");
                var blockId = trElement.down("td").innerHTML.replace(/^\s+|\s+$/g,"");
                var blockTitle = trElement.down("td").next().innerHTML;
                '.
            $chooserJsObject.
            '.setElementValue(blockId);
                '.
            $chooserJsObject.
            '.setElementLabel(blockTitle);
                '.
            $chooserJsObject.
            '.close();
            }
        ';

        return $js;
    }

    /**
     * Prepare Cms static blocks collection.
     *
     * @return \Magento\Backend\Block\Widget\Grid\Extended
     */
    protected function _prepareCollection()
    {
        $this->setCollection($this->_collectionFactory->create());

        return parent::_prepareCollection();
    }

    /**
     * Prepare columns for Cms blocks grid.
     *
     * @return \Magento\Backend\Block\Widget\Grid\Extended
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'chooser_id',
            ['header' => __('App Block ID'), 'align' => 'right', 'index' => 'block_id', 'width' => 50]
        );

        $this->addColumn('chooser_title', ['header' => __('Title'), 'align' => 'left', 'index' => 'title']);

        $this->addColumn(
            'chooser_identifier',
            ['header' => __('Identifier'), 'align' => 'left', 'index' => 'identifier']
        );

        $this->addColumn(
            'chooser_is_active',
            [
                'header' => __('Status'),
                'index' => 'is_active',
                'type' => 'options',
                'options' => [0 => __('Disabled'), 1 => __('Enabled')],
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * Get grid url.
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('cms/block_app/grid', ['_current' => true]);
    }
}