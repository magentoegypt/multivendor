<?php

namespace Vnecoms\BannerManager\Block\Vendors\Banner;

/**
 * Class Edit
 *
 * @category Vnecoms
 * @package  Vnecoms_BannerManager
 * @module   BannerManager
 * @author   Vnecoms Developer Team
 */
class Edit extends \Vnecoms\Vendors\Block\Vendors\Widget\Form\Container
{
    /** @var  \Magento\Framework\Registry */
    protected $_coreRegistry;
    /** @var \Vnecoms\BannerManager\Model\BannerFactory  */
    protected $_bannerFactory;

    /**
     * Edit constructor.
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Vnecoms\BannerManager\Model\BannerFactory $bannerFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        \Vnecoms\BannerManager\Model\BannerFactory $bannerFactory,
        array $data = []
    ) {

        parent::__construct($context, $data);
        $this->_coreRegistry = $registry;
        $this->_bannerFactory = $bannerFactory;
    }


    /**
     * Prepare Global layout
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('banner_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'banner_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'banner_content');
                }
            };

        ";
        return parent::_prepareLayout();
    }

    /**
     * Get edit form container banner text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        if ($this->_coreRegistry->registry('banner')->getId()) {
            return __("Edit Banner '%1'", $this->escapeHtml($this->_coreRegistry->registry('banner')->getTitle()));
        } else {
            return __('New Banner');
        }
    }


    /**
     * Call parent constructor
     *
     * @return void
     */
    protected function _construct()
    {

        $this->_objectId = 'banner_id';
        $this->_blockGroup = 'Vnecoms_BannerManager'; //declare before controller
        $this->_controller = 'vendors_banner';

        parent::_construct();


        if ($this->getRequest()->getParam('banner_id')) {


            $this->buttonList->update('save', 'label', __('Save Banner'));

            //$this->buttonList->add('delete', [ 'label' => __('Delete Banner'), 'class' => '123123']);
            $this->buttonList->update('delete', 'label' , __('Delete Banner'));

            $this->buttonList->add(
                'saveandcontinue',
                [
                    'label' => __('Save and Continue Edit'),
                    'class' => 'save btn btn-success ',
                    'data_attribute' => [
                        'mage-init' => [
                            'button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form'],
                        ],
                    ]
                ],
                -100
            );

            
            if ($bannerId = $this->getRequest()->getParam('banner_id')) {
                $this->_formScripts[] = 'window.banner_id = '.$bannerId.';';
            }

        } else {
            $this->buttonList->add(
                'save_and_continue',
                [
                    'label' => __('Save and Continue Edit'),
                    'class' => 'save btn btn-success',
                    'data_attribute' => [
                        'mage-init' => [
                            'button' => [
                                'event' => 'saveAndContinueEdit',
                                'target' => '#edit_form'
                            ],
                        ],
                    ],
                ],
                10
            );

            $this->buttonList->update(
                'back',
                'class',
                'btn-github'
            );
        }

        if ($this->getRequest()->getParam('saveandclose')) {
            $this->_formScripts[] = 'window.close();';
        }

    }

    /**
     * @return string
     */
    protected function getSaveAndContinueUrl()
    {
        return $this->getUrl(
            '*/*/save',
            [
                '_current' => true,
                'back' => 'edit',
                'active_tab' => '{{tab_id}}',
                'store' => $this->getRequest()->getParam('store'),
                'banner_id' => $this->getRequest()->getParam('banner_id')
            ]
        );
    }

    /**
     * @return string
     */
    public function getDeleteUrl()
    {
        return $this->getUrl('*/*/delete', ['banner_id' => $this->getRequest()->getParam('banner_id')]);
    }

    /**
     * @return string
     */
    protected function getSaveAndCloseWindowUrl()
    {
        return $this->getUrl(
            '*/*/save',
            [
                '_current' => true,
                'back' => 'edit',
                'tab' => '{{tab_id}}',
                'store' => $this->getRequest()->getParam('store'),
                'banner_id' => $this->getRequest()->getParam('banner_id'),
                'saveandclose' => 1
            ]
        );
    }



    /**
     * check action allowed
     *
     * @param $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
}
