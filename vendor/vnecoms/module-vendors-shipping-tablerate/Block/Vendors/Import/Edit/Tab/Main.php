<?php
namespace Vnecoms\VendorsShippingTableRate\Block\Vendors\Import\Edit\Tab;

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
        return __('Import Rate');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     * @codeCoverageIgnore
     */
    public function getTabTitle()
    {
        return __('Import Rate');
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
        $this->setTitle(__('Import Rate'));
    }

    /**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('rate_form_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Import Rate')]
        );
        $countries = $this->_country->toOptionArray(false, 'US');
        unset($countries[0]);


        $fieldset->addField(
            'file_import',
            'file',
            [
                'name' => 'file_import',
                'label' => __('File'),
                'required' => true,
                'note' => __("If you use the importing tool, all the old data will be erased and replaced by new data")
            ]
        );

        $this->setForm($form);
        return parent::_prepareForm();
    }
}
