<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model;

use Amasty\CheckoutCore\Api\Data\ManageCheckoutTabsInterface;
use Amasty\CheckoutCore\Block\Adminhtml\Field\Edit\Group;
use Amasty\CheckoutCore\Block\Adminhtml\Field\Edit\GroupFactory;
use Amasty\CheckoutCore\Model\FieldFactory;
use Amasty\CheckoutCore\Model\Field\Form\SortFields;
use Amasty\CustomerAttributes\Helper\Collection as CustomerAttributesHelper;
use Amasty\Orderattr\Model\Attribute\Attribute as OrderAttribute;
use Amasty\Orderattr\Model\ResourceModel\Attribute\Collection as OrderattrCollection;
use Amasty\Orderattr\Model\ResourceModel\Attribute\CollectionFactory as OrderattrCollectionFactory;
use Magento\Customer\Model\ResourceModel\Attribute\Collection as CustomerAttributeCollection;
use Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Eav\Model\Entity\Attribute as EavAttribute;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Form;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;

class FormManagement
{
    public const ORDER_ATTRIBUTES_DEPEND = 'order_attr';
    public const CUSTOMER_ATTRIBUTES_DEPEND = 'customer_attr';
    public const SHIPPING_STEP = 2;
    public const PAYMENT_STEP = 3;
    public const SHIPPING_METHODS = 4;
    public const PAYMENT_PLACE_ORDER = 5;
    public const ORDER_SUMMARY = 6;

    /**
     * @var GroupFactory
     */
    private $groupFactory;

    /**
     * @var FormFactory
     */
    private $formFactory;

    /**
     * @var Field
     */
    private $fieldSingleton;

    /**
     * @var UrlManagement
     */
    private $urlManagement;

    /**
     * @var FieldFactory
     */
    private $fieldFactory;

    /**
     * @var ModuleEnable
     */
    private $moduleEnable;

    /**
     * @var AttributeCollectionFactory
     */
    private $attributeCollectionFactory;

    /**
     * @var Group
     */
    private $groupRows;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var SortFields
     */
    private $sortFields;

    public function __construct(
        GroupFactory $groupFactory,
        FormFactory $formFactory,
        Field $fieldSingleton,
        UrlManagement $urlManagement,
        FieldFactory $fieldFactory,
        ModuleEnable $moduleEnable,
        AttributeCollectionFactory $attributeCollectionFactory,
        Group $groupRows,
        ObjectManagerInterface $objectManager,
        SortFields $sortFields
    ) {
        $this->groupFactory = $groupFactory;
        $this->formFactory = $formFactory;
        $this->fieldSingleton = $fieldSingleton;
        $this->urlManagement = $urlManagement;
        $this->fieldFactory = $fieldFactory;
        $this->moduleEnable = $moduleEnable;
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->groupRows = $groupRows;
        $this->objectManager = $objectManager;
        $this->sortFields = $sortFields;
    }

    /**
     * @param $tabId
     * @param $storeId
     *
     * @return Form
     * @throws LocalizedException
     */
    public function prepareForm($tabId, $storeId)
    {
        /** @var Form $form */
        $form = $this->formFactory->create();

        $fields = [];

        switch ($tabId) {
            case ManageCheckoutTabsInterface::CUSTOMER_INFO_TAB:
                $form = $this->createCustomFieldsButton($form);
                $form = $this->createOrderFieldsButton($form, self::SHIPPING_STEP);
                $form = $this->createCustomerFieldsButton($form);
                $fields = array_merge(
                    $this->fieldSingleton->getConfig($storeId),
                    $this->getOrderAttributeFields($storeId, [self::SHIPPING_STEP]),
                    $this->getCustomerAttributeFields($storeId)
                );
                break;
            case ManageCheckoutTabsInterface::SHIPPING_METHOD_TAB:
                $form = $this->createOrderFieldsButton($form, self::SHIPPING_METHODS);
                $fields = $this->getOrderAttributeFields($storeId, [self::SHIPPING_METHODS]);
                break;
            case ManageCheckoutTabsInterface::PAYMENT_METHOD_TAB:
                $form = $this->createOrderFieldsButton($form, self::PAYMENT_STEP);
                $fields = $this->getOrderAttributeFields(
                    $storeId,
                    [self::PAYMENT_STEP, self::PAYMENT_PLACE_ORDER]
                );
                break;
            case ManageCheckoutTabsInterface::ORDER_SUMMARY_TAB:
                $form = $this->createOrderFieldsButton($form, self::ORDER_SUMMARY);
                $fields = $this->getOrderAttributeFields($storeId, [self::ORDER_SUMMARY]);
                break;
        }

        $visible = $this->addGroup(
            $form,
            'visible_fields',
            __('Enabled Checkout Fields'),
            1
        );

        $invisible = $this->addGroup(
            $form,
            'invisible_fields',
            __('Disabled Checkout Fields'),
            0
        );

        $this->sortFields->execute($fields);

        /** @var Field $field */
        foreach ($fields as $field) {
            $targetGroup = $field->getData('enabled') ? $visible : $invisible;

            $targetGroup->addRow('field_' . $field->getData('attribute_id'), ['field' => $field]);
        }

        return $form;
    }

    /**
     * @param Form $form
     * @param string $groupId
     * @param string $title
     * @param bool $enabled
     *
     * @return Group
     */
    public function addGroup(Form $form, $groupId, $title, $enabled)
    {
        /** @var Group $group */
        $group = $this->groupFactory->create();
        $group->setId($groupId);
        $group->setRenderer($this->groupRows->getGroupRenderer());
        $group->setData('title', $title);
        $group->setData('enabled', $enabled);

        $form->addElement($group);

        return $group;
    }

    /**
     * @param int $storeId
     * @param array $checkoutSteps
     *
     * @return array
     */
    public function getOrderAttributeFields($storeId, $checkoutSteps)
    {
        $orderAttributes = [];

        if ($this->moduleEnable->isOrderAttributesEnable()) {
            /** @var ObjectManager $objectManager */
            /** @var OrderattrCollectionFactory $orderAttrCollectionFactory */
            $orderAttrCollectionFactory = $this->objectManager->create(OrderattrCollectionFactory::class);
            /** @var OrderattrCollection $orderAttrCollection */
            $orderAttrCollection = $orderAttrCollectionFactory->create();

            if ($checkoutSteps) {
                $orderAttrCollection->addFieldToFilter('checkout_step', ['in' => $checkoutSteps]);
            }

            if ($storeId != Field::DEFAULT_STORE_ID) {
                $orderAttrCollection->addStoreFilter($storeId);
            }

            if ($orderAttrCollection->getSize()) {
                $orderAttributes = $this->prepareAdditionalFields(
                    $orderAttrCollection->getItems(),
                    $storeId,
                    self::ORDER_ATTRIBUTES_DEPEND
                );
            }
        }

        return $orderAttributes;
    }

    /**
     * @param int $storeId
     *
     * @return array
     */
    private function getCustomerAttributeFields($storeId)
    {
        $customerAttributes = [];

        if ($this->moduleEnable->isCustomerAttributesEnable()) {
            /** @var ObjectManager $objectManager */
            $objectManager = ObjectManager::getInstance();
            /** @var AttributeCollectionFactory $attrCollectionFactory */
            $customerAttributesHelper = $objectManager->create(CustomerAttributesHelper::class);
            /** @var CustomerAttributeCollection $attrCollection */
            $attrCollection = $this->attributeCollectionFactory->create()
                ->addVisibleFilter();

            if ($storeId != Field::DEFAULT_STORE_ID) {
                $attrCollection->addFieldToFilter(
                    'store_ids',
                    [
                        ['eq' => $storeId],
                        ['like' => $storeId . ',%'],
                        ['like' => '%,' . $storeId],
                        ['like' => '%,' . $storeId . ',%']
                    ]
                );
            }

            $attrCollection = $customerAttributesHelper->addFilters(
                $attrCollection,
                'eav_attribute',
                [
                    "is_user_defined = 1",
                    "attribute_code != 'customer_activated' "
                ]
            );

            if ($attrCollection->getSize()) {
                $customerAttributes = $this->prepareAdditionalFields(
                    $attrCollection->getItems(),
                    $storeId,
                    self::CUSTOMER_ATTRIBUTES_DEPEND
                );
            }
        }

        return $customerAttributes;
    }

    /**
     * @param array $attributes
     * @param int $storeId
     * @param string $moduleDepend
     *
     * @return array
     */
    private function prepareAdditionalFields($attributes, $storeId, $moduleDepend)
    {
        $additionalAttributes = [];

        /** @var OrderAttribute|EavAttribute $item */
        foreach ($attributes as $item) {
            /** @var Field $fieldModel */
            $fieldModel = $this->fieldFactory->create();

            $frontendLabel = $item->getFrontendLabel();

            if ($storeId != Field::DEFAULT_STORE_ID) {
                $this->prepareStorelabel($item, $storeId, $frontendLabel);
            }

            $isEnabled = $moduleDepend === self::CUSTOMER_ATTRIBUTES_DEPEND ?
                (bool) $item->getUsedInProductListing() :
                (bool) $item->getIsVisibleOnFront();

            $isRequired = $item->getIsRequired()
                || ($moduleDepend === self::CUSTOMER_ATTRIBUTES_DEPEND && $item->getData('required_on_front'))
                || ($moduleDepend === self::ORDER_ATTRIBUTES_DEPEND && $item->getRequiredOnFrontOnly());

            $fieldModel->setData(Field::ENABLED, $isEnabled);
            $fieldModel->setData('attribute_id', $item->getAttributeId());
            $fieldModel->setData('attribute_code', $item->getAttributeCode());
            $fieldModel->setData('label', $frontendLabel);
            $fieldModel->setData('default_label', $item->getFrontendLabel());
            $fieldModel->setData('width', 0);
            $fieldModel->setData('required', $isEnabled && $isRequired);
            $fieldModel->setData('store_id', $storeId);
            $fieldModel->setData('sort_order', $item->getSortingOrder());
            $fieldModel->setData('field_depend', $moduleDepend);
            $fieldModel->setData('frontend_input', $item->getFrontendInput());

            $additionalAttributes[$item->getAttributeCode()] = $fieldModel;
        }

        return $additionalAttributes;
    }

    /**
     * @param Form $form
     * @param int $checkoutStepPosition
     *
     * @return Form
     */
    public function createOrderFieldsButton($form, $checkoutStepPosition)
    {
        $form->addField(
            'order-fields-button',
            'button',
            [
                'onclick' => sprintf(
                    "window.open('%s');",
                    $this->urlManagement->getUrl(
                        'amorderattr/attribute/create',
                        ['position' => $checkoutStepPosition]
                    )
                ),
                'value' => __('Add Order Attribute'),
                'class' => 'action-default scalable',
                'disabled' => !$this->moduleEnable->isOrderAttributesEnable(),
                'title' => __('Install Order Attributes for Magento 2 by Amasty to unlock')
            ]
        );

        return $form;
    }

    /**
     * @param Form $form
     *
     * @return Form
     */
    private function createCustomFieldsButton($form)
    {
        $form->addField(
            'custom-fields-button',
            'button',
            [
                'value' => __('Add Custom Fields'),
                'class' => 'action-default scalable'
            ]
        );

        return $form;
    }

    /**
     * @param Form $form
     *
     * @return Form
     */
    private function createCustomerFieldsButton($form)
    {
        $form->addField(
            'customer-fields-button',
            'button',
            [
                'onclick' => sprintf(
                    "window.open('%s');",
                    $this->urlManagement->getUrl('amcustomerattr/attribute/new')
                ),
                'value' => __('Add Customer Attribute'),
                'class' => 'action-default scalable',
                'disabled' => !$this->moduleEnable->isCustomerAttributesEnable(),
                'title' => __('Install Customer Attributes for Magento 2 by Amasty to unlock')
            ]
        );

        return $form;
    }

    /**
     * @param OrderAttribute|EavAttribute $attribute
     * @param int $storeId
     * @param string $frontendLabel
     */
    private function prepareStorelabel($attribute, $storeId, &$frontendLabel)
    {
        if ($attribute->getStoreLabels()) {
            foreach ($attribute->getStoreLabels() as $store => $label) {
                if ($store == $storeId) {
                    $frontendLabel = $label;
                }
            }
        }
    }
}
