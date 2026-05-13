<?php

namespace Vnecoms\BannerManager\Block\Vendors\Banner\Edit\Tab;

/**
 * Class Implementcode in edit form
 *
 * @category Vnecoms
 * @package  Vnecoms_BannerManager
 * @module   BannerManager
 * @author   Vnecoms Developer Team
 */
class Implementcode extends \Vnecoms\Vendors\Block\Vendors\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * Implementcode constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param array $data
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
     * Get Global layout
     *
     * @param void
     * @return $this
     */
    protected function _prepareLayout()
    {
        return parent::_prepareLayout();
    }

    /**
     * Prepare html
     *
     * @param void
     * @return $this
     */
    protected function _beforeToHtml()
    {
        //$this->setTemplate('Vnecoms_BannerManager::implementcode.phtml');
        return parent::_beforeToHtml();
    }


    /**
     * Get banner data
     *
     * @param void
     * @return mixed
     */
    public function getBanner()
    {
        return $this->_coreRegistry->registry('banner');
    }

    /**
     * Get tab label
     *
     * @param void
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Implement Code');
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
     * Check show tab
     *
     * @param void
     * @return bool
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Check hidden
     *
     * @param void
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }
}