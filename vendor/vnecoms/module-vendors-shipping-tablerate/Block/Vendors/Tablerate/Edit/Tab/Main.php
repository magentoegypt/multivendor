<?php
namespace Vnecoms\VendorsShippingTableRate\Block\Vendors\Tablerate\Edit\Tab;

use Vnecoms\Vendors\Block\Vendors\Widget\Form;
use Vnecoms\Vendors\Block\Vendors\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;

class Main extends Generic implements TabInterface
{

    /**
     * @var \Magento\Directory\Model\Config\Source\Country
     */
    protected $_country;

    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $_regionFactory;

    /**
     * @var \Vnecoms\VendorsShippingTableRate\Model\Source\Config\Condition
     */
    protected $_condition;
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;
    /**
     * Main constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Directory\Model\Config\Source\Country $country,
        \Vnecoms\VendorsShippingTableRate\Model\Source\Config\Condition $condition,
        \Magento\Framework\ObjectManagerInterface $object,
        array $data = []
    ) {
        $this->_objectManager = $object;
        $this->_regionFactory = $regionFactory;
        $this->_country = $country;
        $this->_condition = $condition;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare content for tab
     *
     * @return \Magento\Framework\Phrase
     * @codeCoverageIgnore
     */
    public function getTabLabel()
    {
        return __('Rate Information');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     * @codeCoverageIgnore
     */
    public function getTabTitle()
    {
        return __('Rate Information');
    }

    /**
     * Returns status flag about this tab can be showed or not
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Returns status flag about this tab hidden or not
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * @return Form
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('rate_form');
        $this->setTitle(__('Rate Information'));
    }

    /**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        /** @var \Vnecoms\VendorsShippingTableRate\Model\Tablerate $model */
        $model = $this->_coreRegistry->registry('current_rate');


        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('rate_form_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Rate Information')]
        );
        $countries = $this->_country->toOptionArray(false, 'US');
        $allowedCountriesConfig = explode(",", $this->_scopeConfig->getValue('general/country/allow'));
        if ($countries) {
            $countries[0]['label'] = '*';
            $countries[0]['value'] = '*';
        } else {
            $countries = [['value' => '*', 'label' => '*']];
        }
        $allowedCountries = [];
        foreach($countries as $country){
            if($country['value'] != '*' && !in_array($country['value'], $allowedCountriesConfig)) continue;
            $allowedCountries[] = $country;
        }
        $countries = $allowedCountries;


        if ($model && $model->getId()) {
            $fieldset->addField('rate_id', 'hidden', ['name' => 'rate_id' , "value"=> $model->getId()]);
        }

        $sessionFormValues = (array)$this->_objectManager->get('Vnecoms\Vendors\Model\Session')->getFormData(true);
        $formValues = array_merge($model->getData(), $sessionFormValues);
        if (count($allowedCountriesConfig) == 1) {
            unset($countries[0]);
            $model->setData("dest_country_id", $countries[1]['value']);
            $formValues["dest_country_id"] = $countries[1]['value'];
        }
        $regionCollection = $this->_regionFactory->create()->getCollection()->addCountryFilter(
            $model->getData("dest_country_id") ? $model->getData("dest_country_id") : "US"
        );

        $regions = $regionCollection->toOptionArray();
        if ($regions) {
            $regions[0]['label'] = '*';
        } else {
            $regions = [['value' => '', 'label' => '*']];
        }


        $fieldset->addField(
            'dest_region_id',
            'select',
            ['name' => 'dest_region_id', 'label' => __('State'), 'values' => $regions]
        );

        $fieldset->addField(
            'dest_country_id',
            'select',
            ['name' => 'dest_country_id', 'label' => __('Country'), 'required' => true, 'values' => $countries]
        );
        $render = $this->getLayout()->createBlock('Vnecoms\VendorsShippingTableRate\Block\Vendors\Tablerate\Edit\Tab\PostCode\Renderer');
        $postCodeField = $fieldset->addField(
            'dest_zip',
            'text',
            [
                'name' => 'dest_zip',
                'label' => __('Zip/Post Code'),
                'required' => true,
                'note' => __(
                    "* : matches any;<br />xyz* : matches any that begins with 'xyz'<br />xxx-yyy : matches any from xxx to yyy"
                )
            ]
        );
        $postCodeField->setRenderer($render);

        $conditions = $this->_condition->toOptionArray();
        $fieldset->addField(
            'condition_name',
            'select',
            [
                'name' => 'condition_name',
                'label' => __('Condition Name'),
                'required' => true,
                'values' => $conditions
            ]
        );

        $fieldset->addField(
            'condition_value_from',
            'text',
            [
                'name' => 'condition_value_from',
                'label' => __('Condition Value From'),
                'required' => true,
                'class' => 'validate-not-negative-number'
            ]
        );

        $fieldset->addField(
            'condition_value_to',
            'text',
            [
                'name' => 'condition_value_to',
                'label' => __('Condition Value To'),
                'required' => true,
                'class' => 'validate-not-negative-number'
            ]
        );

        $fieldset->addField(
            'price',
            'text',
            [
                'name' => 'price',
                'label' => __('Price'),
                'required' => true,
                'class' => 'validate-not-negative-number'
            ]
        );

        $fieldset->addField(
            'delivery_type',
            'text',
            [
                'name' => 'delivery_type',
                'label' => __('Delivery Type'),
                'required' => true
            ]
        );
        $form->setValues($formValues);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
