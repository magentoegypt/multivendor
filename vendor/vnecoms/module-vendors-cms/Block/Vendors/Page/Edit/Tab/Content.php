<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Block\Vendors\Page\Edit\Tab;

/**
 * Cms page edit form main tab.
 */
class Content extends \Vnecoms\Vendors\Block\Vendors\Widget\Form\Generic implements
    \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var \Vnecoms\VendorsCms\Model\Wysiwyg\Config
     */
    protected $_wysiwygConfig;

    /**
     * @var \Vnecoms\VendorsCms\Model\Template\FilterProvider
     */
    protected $filterProvider;

    /**
     * @param \Magento\Backend\Block\Template\Context           $context
     * @param \Magento\Framework\Registry                       $registry
     * @param \Magento\Framework\Data\FormFactory               $formFactory
     * @param \Vnecoms\VendorsCms\Model\Wysiwyg\Config          $wysiwygConfig
     * @param \Vnecoms\VendorsCms\Model\Template\FilterProvider $filterProvider
     * @param array                                             $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Vnecoms\VendorsCms\Model\Wysiwyg\Config $wysiwygConfig,
        \Vnecoms\VendorsCms\Model\Template\FilterProvider $filterProvider,
        array $data = []
    ) {
        $this->_wysiwygConfig = $wysiwygConfig;
        $this->filterProvider = $filterProvider;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form.
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        /** @var $model \Vnecoms\VendorsCms\Model\Page */
        $model = $this->_coreRegistry->registry('cms_page');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('page_');

        $fieldset = $form->addFieldset(
            'content_fieldset',
            ['legend' => __('Content'), 'class' => 'fieldset-wide box box-primary']
        );

        $wysiwygConfig = $this->_wysiwygConfig->getConfig(['tab_id' => $this->getTabId()]);

        $fieldset->addField(
            'content_heading',
            'text',
            [
                'name' => 'content_heading',
                'label' => __('Content Heading'),
                'title' => __('Content Heading'),
            ]
        );

        $contentField = $fieldset->addField(
            'content',
            'editor',
            [
                'name' => 'content',
                'label' => __('Content'),
                'title' => __('Content'),
                'style' => 'height:36em;width:100%',
                'required' => false,
                'config' => $wysiwygConfig,
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

        $this->_eventManager->dispatch('vendor_cms_page_edit_tab_content_prepare_form', ['form' => $form]);
        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Prepare label for tab.
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Content');
    }

    /**
     * Prepare title for tab.
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Content');
    }

    /**
     * Returns status flag about this tab can be shown or not.
     *
     * @return bool
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Returns status flag about this tab hidden or not.
     *
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Check permission for passed action.
     *
     * @param string $resourceId
     *
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
}
