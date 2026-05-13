<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\ResourceModel\OrderCustomFields;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Amasty\CheckoutCore\Api\Data\OrderCustomFieldsInterface;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \Amasty\CheckoutCore\Model\OrderCustomFields::class,
            \Amasty\CheckoutCore\Model\ResourceModel\OrderCustomFields::class
        );
    }

    /**
     * @param int $orderId
     *
     * @return Collection
     */
    public function addFieldByOrderId($orderId)
    {
        return $this->addFieldToFilter(OrderCustomFieldsInterface::ORDER_ID, $orderId);
    }

    /**
     * @param int $orderId
     * @param string $customFieldIndex
     *
     * @return Collection
     */
    public function addFieldByOrderIdAndCustomField($orderId, $customFieldIndex)
    {
        return $this->addFieldByOrderId($orderId)
            ->addFieldToFilter(OrderCustomFieldsInterface::NAME, $customFieldIndex);
    }
}
