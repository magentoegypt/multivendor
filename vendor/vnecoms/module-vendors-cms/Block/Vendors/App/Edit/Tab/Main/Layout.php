<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Vnecoms\VendorsCms\Block\Vendors\App\Edit\Tab\Main;

use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Widget Instance page groups (predefined layouts group) to display on.
 *
 * @method \Vnecoms\VendorsCms\Model\App getApp()
 */
class Layout extends \Magento\Backend\Block\Template implements \Magento\Framework\Data\Form\Element\Renderer\RendererInterface
{
    /**
     * @var AbstractElement|null
     */
    protected $_element = null;

    /**
     * @var string
     */
    protected $_template = 'app/edit/layout.phtml';

    /**
     * @var \Magento\Catalog\Model\Product\TypeFactory
     */
    protected $_typeFactory;

    /**
     * @var \Vnecoms\VendorsProduct\Helper\Data
     */
    protected $_productHelper;

    /**
     * @var \Vnecoms\VendorsCms\Model\App\Page\Group\Config
     */
    protected $pageGroupConfig;

    /**
     * @var \Vnecoms\VendorsCms\Block\Vendors\App\Edit\Chooser\Container
     */
    protected $containerResource;

    /**
     * @var \Magento\Theme\Model\ThemeFactory;
     */
    protected $_themesFactory;

    /**
     * Layout constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Catalog\Model\Product\TypeFactory $productType
     * @param \Vnecoms\VendorsProduct\Helper\Data $productHelper
     * @param \Vnecoms\VendorsCms\Model\App\Page\Group\Config $pageGroupConfig
     * @param \Vnecoms\VendorsCms\Block\Vendors\App\Edit\Chooser\Container $container
     * @param \Magento\Theme\Model\ThemeFactory $themeFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Catalog\Model\Product\TypeFactory $productType,
        \Vnecoms\VendorsProduct\Helper\Data $productHelper,
        \Vnecoms\VendorsCms\Model\App\Page\Group\Config $pageGroupConfig,
        \Vnecoms\VendorsCms\Block\Vendors\App\Edit\Chooser\Container $container,
        \Magento\Theme\Model\ThemeFactory $themeFactory,
        array $data = []
    ) {
        $this->_typeFactory = $productType;
        $this->_productHelper = $productHelper;
        $this->pageGroupConfig = $pageGroupConfig;
        $this->containerResource = $container;
        $this->_themesFactory = $themeFactory;
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function getJsonConfig()
    {
        return json_encode($this->getConfig());
    }

    /**
     * Get all config.
     *
     * @return array
     */
    protected function getConfig()
    {
        $config = [];

        $pageGroupTemplate = '';
        $pageGroupTemplate .= '<div class="fieldset-wrapper page_group_container" id="page_group_container_<%- data.id %>">
                                    <div class="fieldset-wrapper-title">
                                        <label for="app_instance[<%- data.id %>][page_group]">Display on <span class="required display-none">*</span></label>
                                        '.$this->getDisplayOnSelectHtml().'
                                        <div class="actions">
                                            '.$this->getRemoveLayoutButtonHtml().'
                                        </div></div>';
        $pageGroupTemplate .= '<div class="fieldset-wrapper-content">';

        foreach ($this->pageGroupConfig->getPageGroups() as $group) {
            $groupObject = \Magento\Framework\App\ObjectManager::getInstance()
                ->get($group['class']);
            $config[$group['id']] = $groupObject->getConfig();

            $pageGroupTemplate .= $config[$group['id']]['template'];
        }

        $pageGroupTemplate .= '</div>';

        $config['pageGroupTemplate'] = $pageGroupTemplate;
        $config['container'] = $this->containerResource->getContainerOptions();

        return $config;
    }

    /**
     * Retrieve theme instance by its identifier.
     *
     * @param int $themeId
     *
     * @return \Magento\Theme\Model\Theme|null
     */
    protected function _getThemeInstance($themeId)
    {
        /** @var \Magento\Theme\Model\ResourceModel\Theme\Collection $themeCollection */
        $themeCollection = $this->_themesFactory->create();

        return $themeCollection->getItemById($themeId);
    }

    /**
     * @return string
     */
    public function getDataRole()
    {
        return 'appInstanceRole';
    }

    /**
     * Render given element (return html of element).
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $this->setElement($element);

        return $this->toHtml();
    }

    /**
     * Setter.
     *
     * @param AbstractElement $element
     *
     * @return $this
     */
    public function setElement(AbstractElement $element)
    {
        $this->_element = $element;

        return $this;
    }

    /**
     * Getter.
     *
     * @return AbstractElement
     */
    public function getElement()
    {
        return $this->_element;
    }

    /**
     * Generate url to get products chooser by ajax query.
     *
     * @return string
     */
    public function getProductsChooserUrl()
    {
        return $this->getUrl('cms/*/products', ['_current' => true]);
    }

    /**
     * Generate url to get reference block chooser by ajax query.
     *
     * @return string
     */
    public function getBlockChooserUrl()
    {
        return $this->getUrl('cms/*/blocks', ['_current' => true]);
    }

    /**
     * Generate url to get template chooser by ajax query.
     *
     * @return string
     */
    public function getTemplateChooserUrl()
    {
        return $this->getUrl('cms/*/template', ['_current' => true]);
    }

    /**
     * Create and return html of select box Display On.
     *
     * @return string
     */
    public function getDisplayOnSelectHtml()
    {
        $selectBlock = $this->getLayout()->createBlock(
            'Magento\Framework\View\Element\Html\Select'
        )->setName(
            'app_instance[<%- data.id %>][page_group]'
        )->setId(
            'app_instance[<%- data.id %>][page_group]'
        )->setClass(
            'required-entry page_group_select select'
        )
        ->setOptions(
            $this->_getDisplayOnOptions()
        );

        return $selectBlock->toHtml();
    }

    /**
     * Retrieve Display On options array.
     * - Categories (anchor and not anchor)
     * - Products (product types depend on configuration)
     * - Generic (predefined) pages (all pages and single layout update).
     *
     * @return array
     */
    protected function _getDisplayOnOptions()
    {
        $options = [];
        $options[] = ['value' => '', 'label' => $this->escapeJsQuote(__('-- Please Select --'))];

        foreach ($this->pageGroupConfig->getPageGroups() as $group) {
            $groupObject = \Magento\Framework\App\ObjectManager::getInstance()
                ->get($group['class']);
            $options[] = ['label' => $this->escapeJsQuote(__($group['label'])), 'value' => $groupObject->getAvailableOptions()];
        }

        return $options;
    }

    /**
     * Generate array of parameters for every container type to create html template.
     *
     * @return array
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function getDisplayOnContainers()
    {
        $container = [];
        foreach ($this->pageGroupConfig->getPageGroups() as $group) {
            $groupObject = \Magento\Framework\App\ObjectManager::getInstance()
                ->get($group['class']);
            if ($containerOption = $groupObject->getDisplayOnContainerOptions()) {
                $container[$group['id']] = $containerOption;
            }
        }

        return $container;
    }

    /**
     * @return string
     */
    public function getDisplayOnContainersJson()
    {
        return json_encode($this->getDisplayOnContainers());
    }

    /**
     * Retrieve add layout button html.
     *
     * @return string
     */
    public function getAddLayoutButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            'Vnecoms\Vendors\Block\Vendors\Widget\Button'
        )->setData(
            [
                'label' => __('Add Layout Update'),
                'onclick' => 'AppInstance.addPageGroup({})',
                'class' => 'btn-success btn-default btn action-add btn-add-layout-button',
            ]
        );

        return $button->toHtml();
    }

    /**
     * Retrieve remove layout button html.
     *
     * @return string
     */
    public function getRemoveLayoutButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            'Vnecoms\Vendors\Block\Vendors\Widget\Button'
        )->setData(
            [
                'label' => $this->escapeJsQuote(__('Remove Layout Update')),
                'onclick' => 'AppInstance.removePageGroup(this)',
                'class' => 'btn btn-default action-delete btn-remove-layout-button',
            ]
        );

        return $button->toHtml();
    }

    /**
     * Prepare and retrieve page groups data of application.
     *
     * @return array
     */
    public function getPageGroups()
    {
        $app = $this->getApp();
        $pageGroups = [];
        if ($app->getPageGroups()) {
            foreach ($app->getPageGroups() as $pageGroup) {
                $pageGroups[] = [
                    'option_id' => $pageGroup['option_id'],
                    'group' => $pageGroup['page_group'],
                    'block' => $pageGroup['block_reference'],
                    'for_value' => $pageGroup['page_for'],
                    'layout_handle' => $pageGroup['layout_handle'],
                    $pageGroup['page_group'].'_entities' => $pageGroup['entities'],
                    'template' => $pageGroup['template'],
                ];
            }
        }

      //  var_dump($pageGroups);die();
        return $pageGroups;
    }
}
