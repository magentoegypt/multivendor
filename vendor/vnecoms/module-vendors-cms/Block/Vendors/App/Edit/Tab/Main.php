<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Widget Instance Main tab block.
 *
 * @author      Vnecoms Core Team <core@vnecoms.com>
 */

namespace Vnecoms\VendorsCms\Block\Vendors\App\Edit\Tab;

class Main extends \Vnecoms\Vendors\Block\Vendors\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry             $registry
     * @param \Magento\Framework\Data\FormFactory     $formFactory
     * @param array                                   $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Internal constructor.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setActive(true);
    }

    /**
     * Prepare label for tab.
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Frontend Application Properties');
    }

    /**
     * Prepare title for tab.
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Frontend Application Properties');
    }

    /**
     * Returns status flag about this tab can be showen or not.
     *
     * @return true
     */
    public function canShowTab()
    {
        return $this->getApp()->isCompleteToCreate();
    }

    /**
     * Returns status flag about this tab hidden or not.
     *
     * @return true
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Getter.
     *
     * @return \Vnecoms\VendorsCms\Model\App
     */
    public function getApp()
    {
        return $this->_coreRegistry->registry('current_app');
    }

    /**
     * Prepare form before rendering HTML.
     *
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        $app = $this->getApp();

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post']]
        );

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Frontend Application Properties'), 'class' => 'fieldset-widget-options box box-primary']);

        if ($app->getId()) {
            $fieldset->addField('app_id', 'hidden', ['name' => 'app_id']);
        }

        $this->_addElementTypes($fieldset);

        $fieldset->addField(
            'code',
            'select',
            [
                'name' => 'code',
                'label' => __('Type'),
                'title' => __('Type'),
                'class' => '',
                'values' => $this->getTypesOptionsArray(),
                'disabled' => true,
            ]
        );

        $fieldset->addField(
            'title',
            'text',
            [
                'name' => 'title',
                'label' => __('Application Title'),
                'title' => __('Application Title'),
                'class' => '',
                'required' => true,
            ]
        );

        $fieldset->addField(
            'sort_order',
            'text',
            [
                'name' => 'sort_order',
                'label' => __('Sort Order'),
                'title' => __('Sort Order'),
                'class' => 'validate-number',
                'required' => false,
                'note' => __('Sort Order of application in the same container'),
            ]
        );

        /* @var $layoutBlock \Vnecoms\VendorsCms\Block\Vendors\App\Edit\Tab\Main\Layout */
        $layoutBlock = $this->getLayout()->createBlock(
            'Vnecoms\VendorsCms\Block\Vendors\App\Edit\Tab\Main\Layout'
        )->setApp(
            $app
        );
        $fieldset = $form->addFieldset('layout_updates_fieldset', ['legend' => __('Layout Updates')]);
        $fieldset->addField('layout_updates', 'note', []);
        $form->getElement('layout_updates_fieldset')->setRenderer($layoutBlock);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Retrieve array (type => name) of available apps.
     *
     * @return array
     */
    public function getTypesOptionsArray()
    {
        return $this->getApp()->getAppsConfigOptionArray();
    }

    /**
     * Initialize form fileds values.
     *
     * @return $this
     */
    protected function _initFormValues()
    {
        $this->getForm()->addValues($this->getApp()->getData());

        return parent::_initFormValues();
    }
}
