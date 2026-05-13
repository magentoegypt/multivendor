<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\ResourceModel\AdditionalFields;

/**
 * @method \Amasty\CheckoutCore\Model\AdditionalFields[] getItems()
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    protected function _construct()
    {
        $this->_init(
            \Amasty\CheckoutCore\Model\AdditionalFields::class,
            \Amasty\CheckoutCore\Model\ResourceModel\AdditionalFields::class
        );
    }
}
