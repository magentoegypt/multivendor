<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Adminhtml Review Edit Form
 */
namespace Vnecoms\RMA\Block\Adminhtml\Request\Edit\Tabs;

class Address extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * RMA data
     *
     * @var \Vnecoms\RMA\Helper\Config
     */
    protected $_requestConfig = null;

    /**
     * Core system store model
     *
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * @var \Magento\Directory\Model\Config\Source\Country
     */
    protected $_country;

    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $_regionFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param \Vnecoms\RMA\Helper\Config $requestconfig
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Vnecoms\RMA\Helper\Config $requestconfig,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Directory\Model\Config\Source\Country $country,
        array $data = []
    ) {
        $this->_requestConfig = $requestconfig;
        $this->_systemStore = $systemStore;
        $this->_regionFactory = $regionFactory;
        $this->_country = $country;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare edit review form
     *
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        $rma = $this->_coreRegistry->registry('current_request');
        $address = $rma->getAddressFromRequest()->getFirstItem();
        $formData = $address->getData();
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'address_form',
                    'action' => $this->getUrl(
                        'vrma/request/saveAddess',
                        [
                            'address_id' => $address->getId(),
                            'ret' => $this->_coreRegistry->registry('ret')
                        ]
                    ),
                    'method' => 'post',
                ],
            ]
        );

        $fieldset = $form->addFieldset(
            'contact_details',
            ['legend' => __('Contact Information'), 'class' => 'fieldset-wide']
        );

        $fieldset->addField(
            'address_id',
            'hidden',
            ['name' => 'address[address_id]','value'=>$address->getId()]
        );

        $fieldset->addField(
            'firstname',
            'text',
            ['label' => __('First Name'), 'required' => true, 'name' => 'address[firstname]' ,'class' => 'local-validation']
        );

        $fieldset->addField(
            'lastname',
            'text',
            ['label' => __('Last Name'), 'required' => true, 'name' => 'address[lastname]','class' => 'local-validation']
        );

        $fieldset->addField(
            'company',
            'text',
            ['label' => __('Company'), 'required' => false, 'name' => 'address[company]']
        );

        $fieldset->addField(
            'telephone',
            'text',
            ['label' => __('Telephone'), 'required' => true, 'name' => 'address[telephone]','class' => 'local-validation']
        );


        $fieldset->addField(
            'fax',
            'text',
            ['label' => __('Fax'), 'required' => false, 'name' => 'address[fax]']
        );

        $fieldset->addField(
            'additional_information',
            'textarea',
            ['label' => __('Additional Information'), 'required' => false, 'name' => 'address[additional_information]']
        );


        $fieldset = $form->addFieldset(
            'address_details',
            ['legend' => __('Return Address'), 'class' => 'fieldset-wide']
        );

        $countries = $this->_country->toOptionArray(false, 'US');
        unset($countries[0]);

        if (!isset($formData['country_id'])) {
            $formData['country_id'] = $this->_scopeConfig->getValue(
                \Magento\Tax\Model\Config::CONFIG_XML_PATH_DEFAULT_COUNTRY,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }

        if (!isset($formData['region_id'])) {
            $formData['region_id'] = $this->_scopeConfig->getValue(
                \Magento\Tax\Model\Config::CONFIG_XML_PATH_DEFAULT_REGION,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }

        $regionCollection = $this->_regionFactory->create()->getCollection()->addCountryFilter(
            $formData['country_id']
        );

        $regions = $regionCollection->toOptionArray();
        if ($regions) {
            $regions[0]['label'] = '*';
        } else {
            $regions = [['value' => '', 'label' => '*']];
        }

        $fieldset->addField(
            'address',
            'text',
            ['label' => __('Address'), 'required' => true, 'name' => 'address[address]']
        );

        $fieldset->addField(
            'city',
            'text',
            ['label' => __('City'), 'required' => true, 'name' => 'address[city]','class' => 'local-validation']
        );


        $fieldset->addField(
            'region_id',
            'select',
            ['name' => 'address[region_id]', 'label' => __('State'), 'values' => $regions]
        );

        $fieldset->addField(
            'country_id',
            'select',
            ['name' => 'address[country_id]', 'label' => __('Country'), 'required' => true, 'values' => $countries,'class' => 'local-validation']
        );

        $fieldset->addField(
            'postcode',
            'text',
            ['label' => __('Postcode'), 'required' => false, 'name' => 'address[postcode]']
        );


        /**
         * check is show button reply
         * @return bool
         */

        if ($rma->getState() == \Vnecoms\RMA\Model\Request::STATE_OPEN) {
            $button = $fieldset->addField(
                'button-update',
                'text',
                [
                    'label' => '',
                ]
            );
            $renderer = $this->getLayout()->createBlock(
                'Vnecoms\RMA\Block\Adminhtml\Request\Edit\Renderer\Address'
            );
            $button->setRenderer($renderer);
        }


        $this->setChild(
            'form_after',
            $this->getLayout()->createBlock('Magento\Backend\Block\Template')->setTemplate('Vnecoms_RMA::request/address/js.phtml')
        );

        $form->setUseContainer(true);
        $form->setValues($formData);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
