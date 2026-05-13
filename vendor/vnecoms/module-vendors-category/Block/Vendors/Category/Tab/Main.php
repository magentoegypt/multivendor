<?php

namespace Vnecoms\VendorsCategory\Block\Vendors\Category\Tab;

class Main extends \Vnecoms\Vendors\Block\Vendors\Widget\Form\Generic
{
    /**
     * @var \Magento\Config\Model\Config\Source\Yesno
     */
    protected $yesno;

    /**
     * @var \Vnecoms\VendorsCategory\Model\Source\Layout
     */
    protected $layoutSource;

    /**
     * @var \Magento\Cms\Model\Wysiwyg\Config
     */
    protected $_wysiwygConfig;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Config\Model\Config\Source\Yesno $yesno,
        \Vnecoms\VendorsCategory\Model\Source\Layout $layoutSource,
        \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig,
        array $data = []
    ) {
        $this->yesno = $yesno;
        $this->_wysiwygConfig = $wysiwygConfig;
        $this->layoutSource = $layoutSource;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Retrieve Category object.
     *
     * @return \Magento\Catalog\Model\Category
     */
    public function getCategory()
    {
        return $this->_coreRegistry->registry('current_category');
    }

    /**
     * Prepare form.
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        $wysiwygConfig = $this->_wysiwygConfig->getConfig(['tab_id' => $this->getTabId()]);

        $config['add_variables'] = false;
        $config['add_widgets'] = false;
        $config['add_directives'] = true;
        $config['use_container'] = true;
        $config['container_class'] = 'hor-scroll';

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Category Information')]);

        if (!$this->getCategory()->getId()) {
            // path
            if ($this->getRequest()->getParam('parent')) {
                $fieldset->addField(
                    'path',
                    'hidden',
                    ['name' => 'path', 'value' => $this->getRequest()->getParam('parent')]
                );
            } else {
                $fieldset->addField('path', 'hidden', ['name' => 'path', 'value' => 1]);
            }
        } else {
            $fieldset->addField('category_id', 'hidden', ['name' => 'category_id', 'value' => $this->getCategory()->getId()]);
            $fieldset->addField(
                'path',
                'hidden',
                ['name' => 'path', 'value' => $this->getCategory()->getPath()]
            );
        }

        $fieldset->addField(
            'is_active',
            'select',
            [
                'name' => 'is_active',
                'label' => __('Enable Category'),
                'title' => __('Enable Category'),
                'required' => true,
                'options' => $this->yesno->toArray(),
            ]
        );

        $fieldset->addField(
            'included_in_menu',
            'select',
            [
                'name' => 'included_in_menu',
                'label' => __('Include In Menu'),
                'title' => __('Include In Menu'),
                'required' => true,
                'options' => $this->yesno->toArray(),
            ]
        );

        $fieldset->addField(
            'name',
            'text',
            [
                'name' => 'name',
                'label' => __('Category Name'),
                'title' => __('Category Name'),
                'required' => true,
                'class' => 'required-entry',
            ]
        );

        $contentSet = $form->addFieldset('content_fieldset', ['legend' => __('Content')]);

        /* TODO: refactor this in future
         * $contentSet->addType(
            'editor-custom',
            '\Vnecoms\VendorsCategory\Block\Vendors\Category\Helper\Form\Wysiwyg'
        );*/

        $contentSet->addField(
            'description',
            'editor',
            [
                'name' => 'description',
                'label' => __('Description'),
                'title' => __('Description'),
                'required' => false,
                'wysiwyg' => true,
                'force_load' => true,
                'config' => $this->_wysiwygConfig->getConfig($config),
                'style' => 'width:725px;height:24em;',
            ]
        );

        $layoutSet = $form->addFieldset('layout_fieldset', ['legend' => __('Design')]);

        $layoutSet->addField(
            'is_hide_product',
            'select',
            [
                'name' => 'is_hide_product',
                'label' => __('Hide Product'),
                'title' => __('Hide Product'),
                'required' => false,
                'options' => $this->yesno->toArray(),
            ]
        );
        if (!$this->getCategory()->getId()) {
            $this->getCategory()->setIsActive(1)->setIncludedInMenu(1);
        }
        $form->addValues($this->getCategory()->getData());
        $this->_eventManager->dispatch('vendor_category_edit_prepare_form', ['form' => $form]);

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
        return __('Category Information');
    }

    /**
     * Prepare title for tab.
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Category Information');
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
