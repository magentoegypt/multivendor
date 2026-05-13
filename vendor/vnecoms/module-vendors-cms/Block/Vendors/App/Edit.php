<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Widget Instance edit container.
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */

namespace Vnecoms\VendorsCms\Block\Vendors\App;

class Edit extends \Vnecoms\Vendors\Block\Vendors\Widget\Form\Container
{
    /**
     * Core registry.
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry           $registry
     * @param array                                 $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Internal constructor.
     */
    protected function _construct()
    {
        $this->_objectId = 'app_id';
        $this->_blockGroup = 'Vnecoms_VendorsCms';
        $this->_controller = 'vendors_app';
        parent::_construct();
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
     * Prepare layout.
     * Adding save_and_continue button.
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        if ($this->getApp()->isCompleteToCreate()) {
            $this->buttonList->add(
                'save_and_edit_button',
                [
                    'label' => __('Save and Continue Edit'),
                    'class' => 'save btn-primary btn',
                    'data_attribute' => [
                        'mage-init' => [
                            'button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form'],
                        ],
                    ],
                ],
                100
            );
        } else {
            $this->removeButton('save');
        }

        return parent::_prepareLayout();
    }

    /**
     * Return translated header text depending on creating/editing action.
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        if ($this->getApp()->getId()) {
            return __('Application "%1"', $this->escapeHtml($this->getApp()->getTitle()));
        } else {
            return __('New Application');
        }
    }

    /**
     * Return validation url for edit form.
     *
     * @return string
     */
    public function getValidationUrl()
    {
        return $this->getUrl('cms/*/validate', ['_current' => true]);
    }

    /**
     * Return save url for edit form.
     *
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->getUrl('cms/*/save', ['_current' => true, 'back' => null]);
    }
}
