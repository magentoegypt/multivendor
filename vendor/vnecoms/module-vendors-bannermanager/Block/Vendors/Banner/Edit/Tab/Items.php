<?php

namespace Vnecoms\BannerManager\Block\Vendors\Banner\Edit\Tab;

/**
 * Class Items in edit form
 *
 * @category Vnecoms
 * @package  Vnecoms_BannerManager
 * @module   BannerManager
 * @author   Vnecoms Developer Team
 */
class Items extends \Vnecoms\Vendors\Block\Vendors\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /** @var \Vnecoms\BannerManager\Model\ItemFactory  */
    protected $_itemFactory;

    /** @var \Magento\Framework\Event\ManagerInterface  */
    protected $_eventManager;

    /**
     * Items constructor
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Vnecoms\BannerManager\Model\ItemFactory $itemFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Vnecoms\BannerManager\Model\ItemFactory $itemFactory,
        array $data = []
    ) {
        $this->_itemFactory = $itemFactory;
        $this->_eventManager = $context->getEventManager();
        parent::__construct($context, $registry, $formFactory, $data);

    }

    /**
     * Prepare block for rendering
     *
     * @param void
     * @return $this
     */
    protected function _beforeToHtml()
    {
        return parent::_beforeToHtml();
    }

    /**
     * Prepare collection
     *
     * @param void
     * @return mixed
     */
    protected function _prepareCollection()
    {
        $collection = $this->_itemFactory->create();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Prepare layout
     *
     * @param void
     * @return $this
     */
    protected function _prepareLayout()
    {
        return parent::_prepareLayout();
    }

    /**
     * Prepare form
     *
     * @param void
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareForm()
    {

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('items_');


        //Upload field
        $uploadFieldset = $form->addFieldset(
            'upload_field',
            [
                'class'  => 'fieldset-wide',
                'legend' => 'Banner Item Upload'
            ]
        );

        $uploadFieldset->addField(
            'filename',
            'text',
            [
                'label' => __('Upload'),
                'title' => __('Upload'),
                'values' => '',
                'name' => 'filename',
                'required' => true
            ]
        );

        /** @var \Vnecoms\BannerManager\Block\Vendors\Banner\Helper\Form\Gallery $renderer1 */
        $renderer1 = $this->getLayout()->createBlock(
            'Vnecoms\BannerManager\Block\Vendors\Banner\Helper\Form\Gallery',
            'banner_item_gallery_upload'
        );
        $form->getElement('filename')->setRenderer($renderer1);

        $this->setForm($form);
        return parent::_prepareForm();
    }

    /**
     * Get tab label
     *
     * @param void
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Banner Items');
    }

    /**
     * Get tab title
     *
     * @param void
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return $this->getTabLabel();
    }

    /**
     * Can show tab
     *
     * @param void
     * @return bool
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Is hidden
     *
     * @param void
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }
}

