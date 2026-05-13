<?php
/**
 * Page layout config model.
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model\App\Page\Group;

abstract class AbstractGroup
{
    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager;

    /**
     * @var \Vnecoms\VendorsCms\Helper\Data
     */
    protected $_cmsHelper;

    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    protected $_layout;

    /**
     * @var Config
     */
    protected $pageGroupConfig;

    protected $_template;

    /**
     * AbstractGroup constructor.
     *
     * @param \Magento\Framework\Event\ManagerInterface $manager
     * @param \Vnecoms\VendorsCms\Helper\Data           $cmsHelper
     */
    public function __construct(
        \Magento\Framework\Event\ManagerInterface $manager,
        \Vnecoms\VendorsCms\Helper\Data $cmsHelper,
        \Vnecoms\VendorsCms\Model\App\Page\Group\Config $pageGroupConfig,
        \Magento\Framework\View\LayoutInterface $layout
    ) {
        $this->_eventManager = $manager;
        $this->_cmsHelper = $cmsHelper;
        $this->pageGroupConfig = $pageGroupConfig;
        $this->_layout = $layout;
    }

    /**
     * @return array
     */
    abstract public function getAvailableOptions();

    /**
     * @return bool|int|string
     */
    abstract public function isEnabled();

    /**
     * process App.
     */
    abstract public function processApp();

    /**
     * @return string
     */
    abstract public function getJsonConfig();

    public function getDisplayOnSelectHtml()
    {
        $selectBlock = $this->_layout->createBlock(
            'Magento\Framework\View\Element\Html\Select'
        )->setName(
            'app_instance[<%- data.id %>][option_group]'
        )->setId(
            'app_instance[<%- data.id %>][option_group]'
        )->setClass(
            'required-entry page_group_select select'
        )->setOptions(
            $this->_getDisplayOnOptions()
        );

        return $selectBlock->toHtml();
    }

    /**
     * get remove layout button html.
     */
    public function getRemoveLayoutButtonHtml()
    {
        $button = $this->_layout->createBlock(
            'Vnecoms\Vendors\Block\Vendors\Widget\Button'
        )->setData(
            [
                'label' => __('Remove Layout Update'),
                'class' => ' btn btn-default action-delete btn-remove-layout-button',
            ]
        );

        return $button->toHtml();
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
        $options[] = ['value' => '', 'label' => __('-- Please Select --')];

        foreach ($this->pageGroupConfig->getPageGroups() as $group) {
            $groupObject = \Magento\Framework\App\ObjectManager::getInstance()
                ->get($group['class']);
            $options[] = ['label' => __($group['label']), 'value' => $groupObject->getAvailableOptions()];
        }

        return $options;
    }

    /**
     * @return string
     */
    protected function getTemplate()
    {
        return \Magento\Framework\App\ObjectManager::getInstance()
            ->create('Magento\Framework\View\Element\Template')
            ->assign('containers', $this->getDisplayOnContainerOptions())
            //->setDisplayOnSelectHtml($this->getDisplayOnSelectHtml())
            //->setRemoveLayoutButtonHtml($this->getRemoveLayoutButtonHtml())

            ->setTemplate($this->_template)->toHtml();
    }
}
