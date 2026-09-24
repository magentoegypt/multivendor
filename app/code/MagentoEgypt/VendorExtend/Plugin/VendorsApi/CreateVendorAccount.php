<?php
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Plugin\VendorsApi;

use MagentoEgypt\VendorExtend\Model\Vendor\AccountCreator;
use Magento\Store\Model\Store;
use Vnecoms\VendorsApi\Model\Data\Vendor as VendorDataModel;
use Vnecoms\VendorsApi\Model\Data\Vendor\ToModel;

/**
 * Replaces ToModel::createVendorAccount() — see AccountCreator for why it is not $proceed-ed.
 */
class CreateVendorAccount
{
    private AccountCreator $accountCreator;

    public function __construct(AccountCreator $accountCreator)
    {
        $this->accountCreator = $accountCreator;
    }

    public function aroundCreateVendorAccount(
        ToModel $subject,
        callable $proceed,
        VendorDataModel $dataModel,
        Store $store
    ) {
        return $this->accountCreator->create($subject, $dataModel, $store);
    }
}
