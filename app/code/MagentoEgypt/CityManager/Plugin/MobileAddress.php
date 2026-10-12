<?php
namespace MagentoEgypt\CityManager\Plugin;
class MobileAddress
{
    public function __construct(private \MagentoEgypt\CityManager\Plugin\CustomerAddress $normalizer, private \Magento\Customer\Api\AddressRepositoryInterface $repository,private \Magento\Directory\Helper\Data $directory, private \MagentoEgypt\CityManager\Model\Settings $settings) {}
    public function aroundSave($subject, callable $proceed, $customerId, $address) {
        if (!$this->settings->enabled()) return $proceed($customerId,$address);
        if((string)$address->getCountryId()!=='AE') return $proceed($customerId,$address);
        // Mstore's private validator unconditionally requires region and postcode.
        // Preserve its ownership guarantee; use Magento's country-aware validator.
        if($address->getId() && (int)$this->repository->getById($address->getId())->getCustomerId()!==(int)$customerId) throw new \Magento\Framework\Exception\NoSuchEntityException(__('The address does not belong to the current customer.'));
        $address->setCustomerId((int)$customerId);
        $this->normalizer->beforeSave($subject,$address);
        return $this->repository->save($address);
    }
}
