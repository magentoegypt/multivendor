<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\BannerManager\Block\Vendors\Banner\App;

/**
 * CMS block chooser for Wysiwyg CMS widget.
 */
class Chooser extends \Vnecoms\Vendors\Block\Vendors\Widget\Grid\Extended
{
    /**
     * @var \Vnecoms\BannerManager\Model\BannerFactory
     */
    protected $_bannerFactory;

    /**
     * @var \Vnecoms\BannerManager\Model\ResourceModel\Banner\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context                         $context
     * @param \Magento\Backend\Helper\Data                                    $backendHelper
     * @param \Vnecoms\BannerManager\Model\BannerFactory                      $bannerFactory
     * @param \Vnecoms\BannerManager\Model\ResourceModel\Banner\CollectionFactory $collectionFactory
     * @param array                                                           $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Vnecoms\BannerManager\Model\BannerFactory $bannerFactory,
        \Vnecoms\BannerManager\Model\ResourceModel\Banner\CollectionFactory $collectionFactory,
        array $data = []
    ) {
        $this->_bannerFactory = $bannerFactory;
        $this->_collectionFactory = $collectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Block construction, prepare grid params.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setDefaultSort('banner_identifier');
        $this->setDefaultDir('ASC');
        $this->setUseAjax(true);
        $this->setDefaultFilter(['chooser_is_active' => '1']);
    }

    /**
     * Prepare chooser element HTML.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element Form Element
     *
     * @return \Magento\Framework\Data\Form\Element\AbstractElement
     */
    public function prepareElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $uniqId = $this->mathRandom->getUniqueHash($element->getId());
        $sourceUrl = $this->getUrl('bannermanager/banner_app/chooser', ['uniq_id' => $uniqId]);

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
            $banner = $this->_bannerFactory->create()->load($element->getValue());
            if ($banner->getId()) {
                $chooser->setLabel($this->escapeHtml($banner->getTitle()));
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
                var bannerId = trElement.down("td").innerHTML.replace(/^\s+|\s+$/g,"");
                var bannerTitle = trElement.down("td").next().innerHTML;
                '.
            $chooserJsObject.
            '.setElementValue(bannerId);
                '.
            $chooserJsObject.
            '.setElementLabel(bannerTitle);
                '.
            $chooserJsObject.
            '.close();
            }
        ';

        return $js;
    }

    /**
     * Prepare Banners collection.
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
            ['header' => __('ID'), 'align' => 'right', 'index' => 'banner_id', 'width' => 50]
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
                'index' => 'status',
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
        return $this->getUrl('bannermanager/banner_app/chooser', ['_current' => true]);
    }
}
