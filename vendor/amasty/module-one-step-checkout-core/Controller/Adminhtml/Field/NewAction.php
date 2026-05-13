<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Controller\Adminhtml\Field;

use Amasty\CheckoutCore\Model\CustomField\CustomerForm;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Customer\Model\Indexer\Address\AttributeProvider;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\ScopeInterface;
use Amasty\CheckoutCore\Model\Field;
use Amasty\CheckoutCore\Model\ResourceModel\Field as FieldResource;
use Amasty\CheckoutCore\Model\FieldFactory;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Setup\EavSetup;

class NewAction extends Action
{
    public const ADMIN_RESOURCE = 'Amasty_CheckoutCore::checkout_settings_fields';

    /**
     * @var FieldResource
     */
    private $fieldResource;

    /**
     * @var FieldFactory
     */
    private $fieldFactory;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var CustomerForm
     */
    private $customerForm;

    public function __construct(
        Context $context,
        FieldResource $fieldResource,
        FieldFactory $fieldFactory,
        EavSetupFactory $eavSetupFactory,
        CustomerForm $customerForm = null // TODO move to not optional
    ) {
        parent::__construct($context);
        $this->fieldResource = $fieldResource;
        $this->fieldFactory = $fieldFactory;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->customerForm = $customerForm ?? ObjectManager::getInstance()->get(CustomerForm::class);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $fields = $this->_request->getParams();
        $storeId = $this->_request->getParam(ScopeInterface::SCOPE_STORE, Field::DEFAULT_STORE_ID);

        if (!is_array($fields)) {
            return $resultRedirect->setPath('*/*', ['_current' => true]);
        }

        foreach ($fields as $fieldId => $fieldData) {
            if ($fieldId === 'key' || $fieldId === ScopeInterface::SCOPE_STORE) {
                continue;
            }

            /** @var Field $fieldModel */
            $fieldModel = $this->fieldFactory->create();

            $index = preg_replace("/[^0-9]/", '', $fieldId);

            /** @var EavSetup $attribute */
            $attribute = $this->createEavAttribute($index);
            $attributeId = $attribute->getAttributeId(AttributeProvider::ENTITY, 'custom_field_' . $index);

            $this->fieldResource->load($fieldModel, $attributeId, 'attribute_id');

            if ($storeId != \Amasty\CheckoutCore\Model\Field::DEFAULT_STORE_ID && !$fieldModel->getData()) {
                $this->createField(Field::DEFAULT_STORE_ID, $attributeId, $index, $fieldModel);
            }

            $this->createField($storeId, $attributeId, $index, $fieldModel);
        }

        return $resultRedirect->setPath('*/*', ['_current' => true, '_query' => '']);
    }

    /**
     * @param int $index
     *
     * @return EavSetup
     */
    private function createEavAttribute($index)
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create();
        $attribute =  $eavSetup->addAttribute(
            AttributeProvider::ENTITY,
            'custom_field_' . $index,
            [
                'group' => 'General',
                'type' => 'varchar',
                'backend' => '',
                'frontend' => '',
                'label' => 'Custom Field ' . $index,
                'input' => 'text',
                'class' => '',
                'source' => '',
                'global' => '',
                'visible' => true,
                'required' => false,
                'user_defined' => true,
                'default' => '',
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => false,
                'unique' => false,
                'apply_to' => ''
            ]
        );

        $dataForm[] = [
            'form_code' => 'customer_address_edit',
            'attribute_id' => $attribute->getAttributeId(AttributeProvider::ENTITY, 'custom_field_' . $index)
        ];
        $this->customerForm->addFormForAttribute($dataForm);

        return $attribute;
    }

    /**
     * @param int $storeId
     * @param int $attributeId
     * @param int $index
     * @param Field $fieldModel
     */
    private function createField($storeId, $attributeId, $index, $fieldModel)
    {
        $fieldModel->unsetData('id');

        $data = [
            'attribute_id' => $attributeId,
            'label' => 'Custom Field ' . $index,
            'sort_order' => 100 + $index,
            'required' => 0,
            'width' => 100,
            'enabled' => 1,
            'store_id' => $storeId
        ];

        $fieldModel->addData($data);
        $this->fieldResource->save($fieldModel);
    }
}
