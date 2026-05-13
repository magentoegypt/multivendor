<?php

namespace Vnecoms\VendorsCms\Block\Vendors\Block\Tab;

use Magento\Backend\Block\Widget\Tab\TabInterface;

/**
 * Class Main in edit form.
 *
 * @category Vnecoms
 * @module   VendorsCms
 *
 * @author   Vnecoms Developer Team
 */
class Main extends \Vnecoms\Vendors\Block\Vendors\Widget\Form\Generic implements TabInterface
{
    const FIELD_NAME_SUFFIX = 'block';

    /**
     * @var \Magento\Config\Model\Config\Source\Yesno
     */
    protected $yesno;

    /**
     * @var \Vnecoms\VendorsCms\Model\Wysiwyg\Config
     */
    protected $_wysiwygConfig;

    /** @var \Vnecoms\Vendors\Model\Session  */
    protected $vendorSession;

    /**
     * @var \Vnecoms\VendorsCms\Model\Template\FilterProvider
     */
    protected $filterProvider;

    /**
     * Main constructor.
     *
     * @param \Magento\Backend\Block\Template\Context           $context
     * @param \Magento\Framework\Registry                       $registry
     * @param \Magento\Framework\Data\FormFactory               $formFactory
     * @param \Magento\Config\Model\Config\Source\Yesno         $yesno
     * @param \Vnecoms\VendorsCms\Model\Wysiwyg\Config          $wysiwygConfig
     * @param \Vnecoms\Vendors\Model\Session                    $vendorSession
     * @param \Vnecoms\VendorsCms\Model\Template\FilterProvider $filterProvider
     * @param array                                             $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Config\Model\Config\Source\Yesno $yesno,
        \Vnecoms\VendorsCms\Model\Wysiwyg\Config $wysiwygConfig,
        \Vnecoms\Vendors\Model\Session $vendorSession,
        \Vnecoms\VendorsCms\Model\Template\FilterProvider $filterProvider,
        array $data = []
    ) {
        $this->yesno = $yesno;
        $this->vendorSession = $vendorSession;
        $this->_wysiwygConfig = $wysiwygConfig;
        $this->filterProvider = $filterProvider;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Retrieve block object.
     *
     * @return \Vnecoms\VendorsCms\Model\Block
     */
    public function getBlock()
    {
        return $this->_coreRegistry->registry('cms_block');
    }

    /**
     * Prepare form.
     *
     * @param void
     *
     * @return $this
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareForm()
    {
        $wysiwygConfig = $this->_wysiwygConfig->getConfig(['tab_id' => $this->getTabId()]);

        /* @var $model \Vnecoms\VendorsCms\Model\Block */
        $model = $this->_coreRegistry->registry('cms_block');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setDataObject($this->getBlock());

        $form->setHtmlIdPrefix('block_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            [
                'legend' => __('Block Configuration'),
                'class' => 'fieldset-wide box box-primary',
            ]
        );

        if ($model->getId()) {
            $fieldset->addField(
                'block_id',
                'hidden',
                ['name' => 'block_id']
            );
        }

        if (!empty($this->getVendor()->getId())) {
            $fieldset->addField(
                'vendor_id',
                'hidden',
                ['name' => 'vendor_id']
            );
        }

        $fieldset->addField(
            'title',
            'text',
            [
                'name' => 'title',
                'label' => __('Title'),
                'title' => __('Title'),
                'required' => false,
                'disabled' => false,
            ]
        );

        $fieldset->addField(
            'identifier',
            'text',
            [
                'name' => 'identifier',
                'label' => __('Identifier'),
                'title' => __('Identifier'),
                'class' => 'validate-identifier',
                'required' => true,
            ]
        );

        $fieldset->addField(
            'is_active',
            'select',
            [
                'label' => __('Status'),
                'title' => __('Status'),
                'name' => 'is_active',
                'required' => true,
                'options' => $model->getAvailableStatuses(),
            ]
        );

        $contentField = $fieldset->addField(
            'content',
            'editor',
            [
                'name' => 'content',
                'label' => __('Content'),
                'title' => __('Content'),
                'required' => false,
                'config' => $wysiwygConfig,
                'style' => 'height:36em;width:100%;',
            ]
        );
        $contentField->setData('note', $this->filterProvider->getFilterNotes());

        // Setting custom renderer for content field to remove label column
        $renderer = $this->getLayout()->createBlock(
            'Vnecoms\Vendors\Block\Vendors\Widget\Form\Renderer\Fieldset\Element'
        )->setTemplate(
            'Vnecoms_VendorsCms::page/edit/form/renderer/content.phtml'
        );

        $form->getElement('content')->setRenderer($renderer);

        if (!$model->getId()) {
            $model->setData('is_active', 1);
        }
        $form->setValues($model->getData());
        //$form->addFieldNameSuffix(self::FIELD_NAME_SUFFIX);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Get Tab Label.
     *
     * @param void
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Block Configuration');
    }

    /**
     * Get Tab Title.
     *
     * @param void
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return $this->getTabLabel();
    }

    /**
     * Can show tab.
     *
     * @param void
     *
     * @return bool
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Allow action.
     *
     * @param $resourceId
     *
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    protected function getVendor()
    {
        if (isset($this->vendorSession)) {
            return $this->vendorSession->getVendor();
        }
    }
}
