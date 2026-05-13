<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Delivery Date for Magento 2 (System)
 */

namespace Amasty\CheckoutDeliveryDate\Model\ResourceModel\Delivery;

use Amasty\CheckoutDeliveryDate\Model\Delivery;
use Magento\Framework\DB\Select;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(Delivery::class, \Amasty\CheckoutDeliveryDate\Model\ResourceModel\Delivery::class);
    }

    /**
     * @param array $quoteIds
     */
    public function addSizeSelectByQuoteIds(array $quoteIds = []): void
    {
        $this->addFieldToFilter('quote_id', ['in' => $quoteIds]);
        $this->getSelect()->reset(Select::COLUMNS);
        $this->getSelect()->columns(['size' => new \Zend_Db_Expr('COUNT(*)')]);
    }
}
