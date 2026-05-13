<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Block\Vendors\Page\Edit\Tab;

/**
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 */
class Design extends \Vnecoms\Vendors\Block\Vendors\Widget\Form\Generic implements
    \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var \Magento\Framework\View\Design\Theme\LabelFactory
     */
    protected $_labelFactory;

    /**
     * @var \Magento\Theme\Model\Layout\Source\Layout
     */
    protected $_pageLayout;

    /**
     * @var \Magento\Framework\View\Model\PageLayout\Config\BuilderInterface
     */
    protected $pageLayoutBuilder;

    /**
     * @param \Magento\Backend\Block\Template\Context                          $context
     * @param \Magento\Framework\Registry                                      $registry
     * @param \Magento\Framework\Data\FormFactory                              $formFactory
     * @param \Magento\Theme\Model\Layout\Source\Layout                        $pageLayout
     * @param \Magento\Framework\View\Design\Theme\LabelFactory                $labelFactory
     * @param \Magento\Framework\View\Model\PageLayout\Config\BuilderInterface $pageLayoutBuilder
     * @param array                                                            $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Theme\Model\Layout\Source\Layout $pageLayout,
        \Magento\Framework\View\Design\Theme\LabelFactory $labelFactory,
        \Magento\Framework\View\Model\PageLayout\Config\BuilderInterface $pageLayoutBuilder,
        array $data = []
    ) {
        $this->pageLayoutBuilder = $pageLayoutBuilder;
        $this->_labelFactory = $labelFactory;
        $this->_pageLayout = $pageLayout;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form tab configuration.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setShowGlobalIcon(true);
    }

    /**
     * Initialise form fields.
     *
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(['data' => ['html_id_prefix' => 'page_']]);

        $model = $this->_coreRegistry->registry('cms_page');

        $layoutFieldset = $form->addFieldset(
            'layout_fieldset',
            ['legend' => __('Page Layout'), 'class' => 'fieldset-wide box box-primary']
        );

        $pageLayout = $this->pageLayoutBuilder->getPageLayoutsConfig()->toOptionArray();
        unset($pageLayout[0]);
        $layoutFieldset->addField(
            'page_layout',
            'select',
            [
                'name' => 'page_layout',
                'label' => __('Layout'),
                'required' => true,
                'values' => $pageLayout,
            ]
        );

        $this->_eventManager->dispatch('vendor_cms_page_edit_tab_design_prepare_form', ['form' => $form]);

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
        return __('Design');
    }

    /**
     * Prepare title for tab.
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Design');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
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
