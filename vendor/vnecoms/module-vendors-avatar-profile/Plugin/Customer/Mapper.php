<?php
namespace Vnecoms\VendorsAvatarProfile\Plugin\Customer;

use Magento\Customer\Api\Data\CustomerInterface;

class Mapper
{
    /**
     * @param \Magento\Framework\App\ActionInterface $subject
     * @param callable $proceed
     * @param \Magento\Framework\App\RequestInterface $request
     * @return mixed
     */
    public function aroundToFlatArray(
        \Magento\Customer\Model\Customer\Mapper $subject,
        \Closure $proceed,
        CustomerInterface $customer
    ) {
        $attributes = $proceed($customer);
        if (isset($attributes['profile_picture'])) {
            unset($attributes['profile_picture']);
        }
        return $attributes;
    }
}
