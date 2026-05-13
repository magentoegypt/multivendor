<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Observer\Order;

use Amasty\CheckoutCore\Api\Data\CustomFieldsConfigInterface;
use Amasty\CheckoutCore\Model\CustomFormatFlag;
use Amasty\CheckoutCore\Model\Field;
use Magento\Framework\Event\ObserverInterface;
use Amasty\CheckoutCore\Model\ResourceModel\OrderCustomFields\Collection;
use Amasty\CheckoutCore\Model\ResourceModel\OrderCustomFields\CollectionFactory;
use Amasty\CheckoutCore\Api\Data\OrderCustomFieldsInterface;
use Magento\Sales\Model\Order\Address;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class RendererAddressFormat implements ObserverInterface
{
    /**
     * @var CollectionFactory
     */
    private $customFieldsCollectionFactory;

    /**
     * @var Field
     */
    private $fieldSingleton;

    public function __construct(
        CollectionFactory $customFieldsCollectionFactory,
        Field $fieldSingleton
    ) {
        $this->customFieldsCollectionFactory = $customFieldsCollectionFactory;
        $this->fieldSingleton = $fieldSingleton;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var Address $address */
        $address = $observer->getAddress();

        if (!$address->getOrder()) {
            return;
        }

        if (CustomFormatFlag::$flag) {
            /** @var \Magento\Framework\DataObject $formatType */
            $formatType = $observer->getType();
            $formatType->setDefaultFormat($formatType->getDefaultFormat() . $this->getFormatAdditions($address));

            CustomFormatFlag::$flag = false;
        }

        /** @var Collection $customFieldsCollection */
        $customFieldsCollection = $this->customFieldsCollectionFactory->create();
        $customFieldsCollection->addFieldByOrderId($address->getOrder()->getId());

        $customFieldsData = $this->prepareCustomFieldData($customFieldsCollection, $address->getAddressType());

        $address->addData($customFieldsData);
    }

    private function prepareCustomFieldData(Collection $orderCustomFieldsCollection, string $addressType): array
    {
        $customFieldsData = [];

        foreach ($orderCustomFieldsCollection->getItems() as $orderCustomField) {
            $orderCustomField = $orderCustomField->getData();
            $customFieldsData[$orderCustomField[OrderCustomFieldsInterface::NAME]]
                = $orderCustomField[$addressType . '_value'];
        }

        return $customFieldsData;
    }

    private function getFormatAdditions(Address $address): string
    {
        $fieldConfig = $this->fieldSingleton->getConfig((int) $address->getOrder()->getStoreId());

        $index = CustomFieldsConfigInterface::CUSTOM_FIELD_INDEX;
        $countOfCustomFields = CustomFieldsConfigInterface::COUNT_OF_CUSTOM_FIELDS;
        for ($additionalFormat = ''; $index <= $countOfCustomFields; $index++) {
            $additionalFormat .= $this->formatCustomField($this->getLabel($fieldConfig, $index), $index);
        }

        return $additionalFormat;
    }

    private function formatCustomField(string $label, int $index): string
    {
        return "{{depend custom_field_$index}}<br /> $label: {{var custom_field_$index}}{{/depend}}";
    }

    private function getLabel(array $fieldConfig, int $index): string
    {
        return isset($fieldConfig['custom_field_' . $index]) ?
            $fieldConfig['custom_field_' . $index]->getLabel() :
            __('Custom Field ') . $index;
    }
}
