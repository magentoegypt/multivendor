<?php
/**
 * Copyright Â© 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Widget Instance Settings tab block.
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */

namespace Vnecoms\VendorsCms\Block\Vendors\App\Edit\Tab;

class Settings extends \Vnecoms\Vendors\Block\Vendors\Widget\Form\Generic implements
    \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * Core registry.
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

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
        return __('Settings');
    }

    /**
     * Prepare title for tab.
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Settings');
    }

    /**
     * Returns status flag about this tab can be showen or not.
     *
     * @return true
     */
    public function canShowTab()
    {
        return !(bool) $this->getApp()->isCompleteToCreate();
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
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post']]
        );

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Settings'), 'class' => 'box box-primary']);

        $this->_addElementTypes($fieldset);

        $fieldset->addField(
            'code',
            'select',
            [
                'name' => 'code',
                'label' => __('Type'),
                'title' => __('Type'),
                'required' => true,
                'values' => $this->getTypesOptionsArray(),
            ]
        );

        $continueButton = $this->getLayout()->createBlock(
            'Vnecoms\Vendors\Block\Vendors\Widget\Button'
        )->setData(
            [
                'label' => __('Continue'),
                'onclick' => "setSettings('".$this->getContinueUrl()."', 'code')",
                'class' => 'save btn btn-default btn-success',
            ]
        );
        $fieldset->addField('continue_button', 'note', ['text' => $continueButton->toHtml()]);

        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Return url for continue button.
     *
     * @return string
     */
    public function getContinueUrl()
    {
        return $this->getUrl(
            'cms/*/*',
            [
                '_current' => true,
                'code' => '<%- data.code %>',
                //   'theme_id' => '<%- data.theme_id %>',
                '_escape_params' => false
            ]
        );
    }

    /**
     * Retrieve array (app_type => widget_name) of available widgets.
     *
     * @return array
     */
    public function getTypesOptionsArray()
    {
        $apps = $this->getApp()->getAppsConfigOptionArray();
        array_unshift($apps, ['value' => '', 'label' => __('-- Please Select --')]);

        return $apps;
    }

    /**
     * User-defined apps sorting by Name.
     *
     * @param array $a
     * @param array $b
     *
     * @return bool
     */
    protected function _sortApps($a, $b)
    {
        return strcmp($a['label'], $b['label']);
    }
}