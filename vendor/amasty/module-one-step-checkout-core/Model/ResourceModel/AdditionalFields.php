<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\ResourceModel;

use Amasty\CheckoutCore\Api\Data\AdditionalFieldsInterface;

class AdditionalFields extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public const MAIN_TABLE = 'amasty_amcheckout_additional';

    protected function _construct()
    {
        $this->_init(self::MAIN_TABLE, AdditionalFieldsInterface::ID);
    }
}
