<?php
namespace MagentoEgypt\VendorExtend\Model\Data\Sale;

use MagentoEgypt\VendorExtend\Api\Data\Sale\OrderInterface;
use Vnecoms\VendorsApi\Model\Data\Sale\Order as BaseOrder;

class Order extends BaseOrder implements OrderInterface
{
    /**
     * Get Commission
     *
     * @return float|null
     */
    public function getCommission()
    {
        return $this->_get(self::COMMISSION);
    }

    /**
     * Set Commission
     *
     * @param float $commission
     * @return $this
     */
    public function setCommission($commission)
    {
        return $this->setData(self::COMMISSION, $commission);
    }
}