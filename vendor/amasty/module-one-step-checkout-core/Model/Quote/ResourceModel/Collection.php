<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Quote\ResourceModel;

class Collection extends \Magento\Quote\Model\ResourceModel\Quote\Collection
{
    /**
     * @return int|string
     */
    public function getSize()
    {
        return $this->getConnection()->fetchOne($this->getSelectCountSql(), $this->_bindParams);
    }
}
