<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Plugin\Sales\Admin\Order\Address;

use Amasty\CheckoutCore\Api\Data\CustomFieldsConfigInterface;
use Amasty\CheckoutCore\Api\Data\OrderCustomFieldsInterface;
use Amasty\CheckoutCore\Model\ResourceModel\OrderCustomFields\Collection;
use Amasty\CheckoutCore\Model\ResourceModel\OrderCustomFields\CollectionFactory;

class Form
{
    /**
     * @var CollectionFactory
     */
    private $orderCustomFieldsCollection;

    public function __construct(
        CollectionFactory $orderCustomFieldsCollection
    ) {
        $this->orderCustomFieldsCollection = $orderCustomFieldsCollection;
    }

    /**
     * @param \Magento\Sales\Block\Adminhtml\Order\Address\Form $subject
     * @param array $formValues
     *
     * @return array
     */
    public function afterGetFormValues(\Magento\Sales\Block\Adminhtml\Order\Address\Form $subject, $formValues)
    {
        foreach (\Amasty\CheckoutCore\Api\Data\CustomFieldsConfigInterface::CUSTOM_FIELDS_ARRAY as $attributeCode) {
            /** @var Collection $orderCustomFieldsCollection */
            $orderCustomFieldsCollection = $this->orderCustomFieldsCollection->create();
            $orderCustomFieldsCollection->addFieldByOrderIdAndCustomField(
                $formValues['parent_id'],
                $attributeCode
            );
            $orderCustomFieldsData = $orderCustomFieldsCollection->getFirstItem()->getData();

            if ($orderCustomFieldsData) {
                $formValues[$orderCustomFieldsData[OrderCustomFieldsInterface::NAME]] =
                    $orderCustomFieldsData[$formValues['address_type'] . '_value'];
            }
        }

        return $formValues;
    }
}
