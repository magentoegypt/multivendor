<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Delivery Date for Magento 2 (System)
 */

namespace Amasty\CheckoutDeliveryDate\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Delivery extends AbstractDb
{
    public const MAIN_TABLE = 'amasty_amcheckout_delivery';

    protected function _construct()
    {
        $this->_init(self::MAIN_TABLE, 'id');
    }
}
