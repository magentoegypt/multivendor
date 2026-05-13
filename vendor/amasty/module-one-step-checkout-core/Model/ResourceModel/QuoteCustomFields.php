<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\ResourceModel;

use Amasty\CheckoutCore\Api\Data\QuoteCustomFieldsInterface;

class QuoteCustomFields extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public const MAIN_TABLE = 'amasty_amcheckout_quote_custom_fields';

    protected function _construct()
    {
        $this->_init(self::MAIN_TABLE, QuoteCustomFieldsInterface::ID);
    }
}
