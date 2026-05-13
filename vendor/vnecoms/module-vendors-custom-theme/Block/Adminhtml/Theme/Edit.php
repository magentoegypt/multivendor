<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Vendor Group Edit Block
 */
namespace Vnecoms\VendorsCustomTheme\Block\Adminhtml\Theme;

class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
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
     * Initialize form
     * Add standard buttons
     * Add "Save and Apply" button
     * Add "Save and Continue" button
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'Vnecoms_VendorsCustomTheme';
        $this->_controller = 'Adminhtml_Theme';
        
        parent::_construct();
        $this->updateButton('save', 'label', __('Save Theme'));

        $theme = $this->_coreRegistry->registry('current_theme');
        if($theme->getId()){
            $this->buttonList->add(
                'add_new_section',
                [
                    'class' => 'add',
                    'label' => __('Add Config Sections'),
                    'onclick' => "jQuery('#section-popup-mpdal').modal('openModal');",
                ],
                5
            );
        }
        
        $this->buttonList->add(
            'save_and_continue_edit',
            [
                'class' => 'save',
                'label' => __('Save and Continue Edit'),
                'data_attribute' => [
                    'mage-init' => ['button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form']],
                ]
            ],
            10
        );
        
        return $this;
    }

    /**
     * Getter for form header text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        $auction = $this->_coreRegistry->registry('current_theme');
        if ($auction->getAuctionId()) {
            return __("Edit Theme '%s'", $this->escapeHtml($auction->getName()));
        } else {
            return __('New Theme');
        }
    }
}
