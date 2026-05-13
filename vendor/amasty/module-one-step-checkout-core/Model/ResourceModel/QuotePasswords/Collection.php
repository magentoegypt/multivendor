<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\ResourceModel\QuotePasswords;

use Amasty\CheckoutCore\Model\QuotePasswords;
use Amasty\CheckoutCore\Model\ResourceModel\QuotePasswords as ResourceQuotePasswords;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(QuotePasswords::class, ResourceQuotePasswords::class);
    }
}
