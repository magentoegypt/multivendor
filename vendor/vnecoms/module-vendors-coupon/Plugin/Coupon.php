<?php
namespace Vnecoms\VendorsCoupon\Plugin;

class Coupon
{
    /**
     * Check if code exists in vendor coupon table
     *
     * @param string $code
     * @return bool
     */
    
    public function aroundExists(
        \Magento\SalesRule\Model\ResourceModel\Coupon $subject,
        \Closure $proceed,
        $code
    ) {
        $result = $proceed($code);
        if($result === false){
            $connection = $subject->getConnection();
            $select = $connection->select();
            $select->from($subject->getTable('ves_vendor_coupon'), 'code');
            $select->where('code = :code');
            
            if ($connection->fetchOne($select, ['code' => $code]) === false) {
                return false;
            }
        }
        
        return true;
    }
}
