<?php
namespace Vnecoms\Vendors\Block\Adminhtml\Customer\Create\Tab;

use Magento\Backend\Block\Widget\Form;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Customer\Model\AccountManagement;

class SellerMain extends Generic implements TabInterface
{

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;


    /**
     *
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
        $this->_coreRegistry = $registry;
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
        return __('Create Seller For Customer');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     * @codeCoverageIgnore
     */
    public function getTabTitle()
    {
        return __('Customer Information');
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
     * Prepare form before rendering HTML
     *
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */

        $model = $this->_coreRegistry->registry('current_customer');

        $form = $this->_formFactory->create();
//        $form->setHtmlIdPrefix('vendor_');
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Customer Information')]);
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $website = $om->get('Vnecoms\Vendors\Model\Source\Website');

        $field = $fieldset->addField(
            'customer_id',
            'label',
            [
                'name' => 'customer_id',
                'label' => __('Customer'),
                'title' => __('Customer'),
                'class' => 'customner-option',
                'css_class' => 'badge-vendor',
                'required' => true,
            ]
        );
        $data = [
            'button' => ['open' => __('Select Customer...')],
            'type' => 'Vnecoms\Vendors\Block\Adminhtml\Vendor\Chooser'
        ];
        $helperBlock = $this->getLayout()->createBlock(
            'Vnecoms\Vendors\Block\Adminhtml\Vendor\Chooser',
            '',
            ['data' => $data]
        );
        if ($helperBlock instanceof \Magento\Framework\DataObject) {
            $helperBlock->setConfig(
                $data
            )->setFieldsetId(
                $fieldset->getId()
            )->prepareElementHtml(
                $field
            );
        }

        $this->_eventManager->dispatch('ves_vendors_vendor_tab_main_prepare_after',array('tab'=>$this,'form'=>$form,'fieldset'=>$fieldset));
        $data = $model->getData();
        $form->setValues($data);
        $this->setForm($form);
        return parent::_prepareForm();
    }


    /**
     * Get minimum password length
     *
     * @return string
     * @since 100.1.0
     */
    public function getMinimumPasswordLength()
    {
        return $this->_scopeConfig->getValue(AccountManagement::XML_PATH_MINIMUM_PASSWORD_LENGTH);
    }

    /**
     * Get number of password required character classes
     *
     * @return string
     * @since 100.1.0
     */
    public function getRequiredCharacterClassesNumber()
    {
        return $this->_scopeConfig->getValue(AccountManagement::XML_PATH_REQUIRED_CHARACTER_CLASSES_NUMBER);
    }
}
