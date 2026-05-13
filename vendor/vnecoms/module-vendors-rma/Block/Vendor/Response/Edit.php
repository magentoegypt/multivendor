<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 03/21/2016
 * Time: 09:10 AM
 */

namespace Vnecoms\VendorsRMA\Block\Vendor\Response;

use Vnecoms\Vendors\Block\Vendors\Widget\Form\Container;

class Edit extends Container
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
     * Department edit block
     *
     * @return void
     */
    protected function _construct()
    {

        $this->_objectId = 'id';
        $this->_blockGroup = 'Vnecoms_VendorsRMA';
        $this->_controller = 'vendor_response';
        parent::_construct();
        $id = $this->_coreRegistry->registry('current_reponse')->getId();
        if ($id){
            $this->addButton(
                'delete',
                [
                    'label' => __('Delete'),
                    'onclick' => 'deleteConfirm(' . json_encode(__('Are you sure you want to do this?'))
                        . ','
                        . json_encode($this->getDeleteUrl()
                        )
                        . ')',
                    'class' => 'delete',
                    'level' => -1
                ]
            );
        }
//        if ($this->_isAllowedAction('Vnecoms_VendorsFaq::vendorsfaq')) {
        $this->buttonList->update('save', 'label', __('Save'));
        $this->buttonList->add(
            'saveandcontinue',
            [
                'label' => __('Save and Continue Edit'),
                'class' => 'btn-success save fa fa-check-circle',
                'data_attribute' => [
                    'mage-init' => [
                        'button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form'],
                    ],
                ]
            ],
            -100
        );

    }

    /**
     * Get header with Department name
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        if ($this->_coreRegistry->registry('current_reponse')->getId()) {
            return __("Edit '%1'", $this->escapeHtml($this->_coreRegistry->registry('current_reponse')->getTitle()));
        } else {
            return __('New Response');
        }
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    /**
     * Getter of url for "Save and Continue" button
     * tab_id will be replaced by desired by JS later
     *
     * @return string
     */
    protected function _getSaveAndContinueUrl()
    {

        return $this->getUrl('*/*/save', ['_current' => true, 'back' => 'edit', 'active_tab' => '']);
    }
    public function getDeleteUrl(array $args = [])
    {

        return $this->getUrl('*/*/delete',['_current' => true, 'back' => 'edit', 'active_tab' => '{{response_id}}']);
    }
}