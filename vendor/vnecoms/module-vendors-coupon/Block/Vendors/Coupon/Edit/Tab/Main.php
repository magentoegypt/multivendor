<?php

namespace Vnecoms\VendorsCoupon\Block\Vendors\Coupon\Edit\Tab;

/**
 * Class Main in edit form
 *
 * @category Vnecoms
 * @package  Vnecoms_BannerManager
 * @module   BannerManager
 * @author   Vnecoms Developer Team
 */
class Main extends \Vnecoms\Vendors\Block\Vendors\Widget\Form\Generic
    implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var \Vnecoms\VendorsCoupon\Helper\Data $helperData
     */
    protected $_helperData;

    /**
     * Main constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Vnecoms\VendorsCoupon\Helper\Data $helperData
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Vnecoms\VendorsCoupon\Helper\Data $helperData,
        array $data = []
    ) {
        $this->_helperData = $helperData;
        parent::__construct($context, $registry, $formFactory, $data);
    }


    /**
     * Prepare form
     *
     * @param void
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareForm()
    {

        /* @var $model \Vnecoms\VendorsCoupon\Model\Coupon */
        $model = $this->_coreRegistry->registry('coupon');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('coupon_');


        $fieldset = $form->addFieldset(
            'coupon_info_fieldset',
            [
                'legend' => __('Coupon Information'),
                'class' => 'fieldset-wide admin__form-field-control'
            ]
        );

        if ($model->getId()) {
            $fieldset->addField(
                'coupon_id',
                'hidden',
                ['name' => 'coupon_id']
            );
        }

        $fieldset->addField(
            'code',
            'label',
            [
                'name' => 'code',
                'label' => __('Coupon Code'),
                'title' => __('Coupon Code'),
                'required' => true,
            ]
        );

        $fieldset->addField(
            'usage_limit',
            'text',
            [
                'name' => 'usage_limit',
                'label' => __('Usage Limit'),
                'title' => __('Usage Limit'),
                'class' => 'validate-number',
                'disabled' => false,
                'note' => __('Leave zero or blank for unlimited.'),
            ]
        );
        $fieldset->addField(
            'times_used',
            'label',
            [
                'name' => 'times_used',
                'label' => __('Time Used'),
                'title' => __('Time Used'),
                'disabled' => false
            ]
        );
        $dateFormat = $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT);
        $fieldset->addField(
            'from_date',
            'date',
            [
                'name' => 'from_date',
                'date_format' => 'M/d/yy',
                'label' => __('From'),
                'title' => __('From'),
                'disabled' => false
            ]
        );
        $fieldset->addField(
            'to_date',
            'date',
            [
                'name' => 'to_date',
                'date_format' => 'M/d/yy',
                'label' => __('To'),
                'title' => __('To'),
                'disabled' => false
            ]
        );

        $actions = [];
        $listActions = $this->_helperData->getDiscountActions();
        foreach ($listActions as $key => $action) {
            $actions[$key] = __($action["name"]);
        }

        $fieldset->addField(
            'action',
            'select',
            [
                'name' => 'action',
                'label' => __('Discount Action'),
                'title' => __('Discount Action'),
                'required' => true,
                'disabled' => false,
                'options' => $actions
            ]
        );

        $fieldset->addField(
            'buy_x',
            'text',
            [
                'name' => 'buy_x',
                'label' => __('Buy X'),
                'title' => __('Buy X'),
                'required' => false,
                'default' => '0',
                'class' => 'validate-number',
                'disabled' => false
            ]
        );

        $fieldset->addField(
            'amount',
            'text',
            [
                'name' => 'amount',
                'label' => __('Discount Amount'),
                'title' => __('Discount Amount'),
                'required' => true,
                'class' => 'validate-number',
                'disabled' => false
            ]
        );


        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Get Tab Label
     *
     * @param void
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Coupon Information');
    }

    /**
     * Get Tab Title
     *
     * @param void
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return $this->getTabLabel();
    }

    /**
     * Can show tab
     *
     * @param void
     * @return bool
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Allow action
     *
     * @param $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

}
