<?php

namespace Vnecoms\BannerManager\Block\Vendors\Banner\Edit\Tab;

/**
 * Class Main in edit form
 *
 * @category Vnecoms
 * @package  Vnecoms_BannerManager
 * @module   BannerManager
 * @author   Vnecoms Developer Team
 */
class Main extends \Vnecoms\Vendors\Block\Vendors\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    const FIELD_NAME_SUFFIX = 'banner';
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    protected $vendorSession;

    /**
     * Main constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        array $data = []
    ) {
        $this->_systemStore = $systemStore;
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

        /* @var $model \Vnecoms\BannerManager\Model\Banner */
        $model = $this->_coreRegistry->registry('banner');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('banner_');


        $fieldset = $form->addFieldset(
            'base_fieldset',
            [
                'legend' => __('Banner Information'),
                'class' => 'fieldset-wide'
            ]
        );

        if ($model->getId()) {
            $fieldset->addField(
                'banner_id',
                'hidden',
                ['name' => 'banner_id']
            );
        }

        if (!empty($this->getVendor()->getId())) {
            $fieldset->addField(
                'vendor_id',
                'hidden',
                ['name' => 'vendor_id']
            );
        }

        $fieldset->addField(
            'identifier',
            'text',
            [
                'name' => 'identifier',
                'label' => __('Identifier'),
                'title' => __('Identifier'),
                'required' => true,
                'disabled' => '',
                'class' => ''
            ]
        );


        $fieldset->addField(
            'title',
            'text',
            [
                'name' => 'title',
                'label' => __('Banner Title'),
                'title' => __('Banner Title'),
                'required' => true,
                'disabled' => false
            ]
        );


        $fieldset->addField(
            'status',
            'select',
            [
                'label' => __('Status'),
                'title' => __('Status'),
                'name' => 'status',
                'required' => true,
                'options' => $model->getAvailableStatuses()
            ]
        );

       /* $fieldset->addField(
            'display_description',
            'select',
            [
                'label'     => __('Display Description'),
                'name'      => 'display_description',
                'disabled'  => '',
                'values'    => [
                    1   => __('Yes'),
                    0   => __('No')
                ],
            ]
        );

        $fieldset->addField(
            'description',
            'textarea',
            [
                'label' => __('Description'),
                'title' => __('Description'),
                'name' => 'description',
                'required' => false

            ]
        );*/

        $dateFormat = $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT);
        $fieldset->addField(
            'from_date',
            'date',
            [
                'label' => __('Display From '),
                'title' => __('Display From '),
                'name' => 'from_date',
                'date_format' => 'M/d/yy',
                'time_format' => 'h:mm a',
                'disabled' => false,
                'required' => false

            ]
        );

        $fieldset->addField(
            'to_date',
            'date',
            [
                'label' => __('Display To '),
                'title' => __('Display To '),
                'name' => 'to_date',
                'date_format' => 'M/d/yy',
                'time_format' => 'h:mm a',
                'disabled' => false,
                'required' => false

            ]
        );

        if (!$model->getId()) {
            $model->setData('status', 1);
        }


        $form->setValues($model->getData());
        //$form->addFieldNameSuffix(self::FIELD_NAME_SUFFIX);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Processing block html after rendering
     *
     * @param string $html
     * @return string
     */
    protected function _afterToHtml($html)
    {
        $form = $this->getForm();
        $htmlIdPrefix = $form->getHtmlIdPrefix();

        /**
         * Form template has possibility to render child block 'form_after', but we can't use it because parent
         * form creates appropriate child block and uses this alias. In this case we can't use the same alias
         * without core logic changes, that's why the code below was moved inside method '_afterToHtml'.
         */
        /** @var $formAfterBlock \Magento\Backend\Block\Widget\Form\Element\Dependence */
        /*$formAfterBlock = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Form\Element\Dependence',
            'vendors.block.widget.form.description.dependence'
        );
        $formAfterBlock->addFieldMap(
            $htmlIdPrefix . 'display_description',
            'display_description'
        )->addFieldMap(
            $htmlIdPrefix . 'description',
            'description'
        )->addFieldDependence(
            'description',
            'display_description',
            '1'
        );

        $html = $html . $formAfterBlock->toHtml();*/

        return $html;
    }

    /**
     * Get Tab Label
     *
     * @param void
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Banner Information');
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

    public function getVendor()
    {
        return ($this->vendorSession != null) ? $this->vendorSession->getVendor() : \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Vnecoms\Vendors\Model\Session')->getVendor();
    }
}