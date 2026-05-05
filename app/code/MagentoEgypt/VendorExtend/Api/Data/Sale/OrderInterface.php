<?php
namespace MagentoEgypt\VendorExtend\Api\Data\Sale;

interface OrderInterface extends \Vnecoms\VendorsApi\Api\Data\Sale\OrderInterface
{
    /*
     * Commission
     */
    const COMMISSION = 'commission';
    
    /**
     * Get Commission
     *
     * @return float|null
     */
    public function getCommission();

    /**
     * Set Commission
     *
     * @param float $commission
     * @return $this
     */
    public function setCommission($commission);
}