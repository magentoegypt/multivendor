<?php
namespace MagentoEgypt\VendorExtend\Model;

use MagentoEgypt\VendorExtend\Api\VendorRepositoryInterface;
use Vnecoms\Vendors\Model\VendorFactory;


class VendorRepository implements VendorRepositoryInterface
{
    protected $vendorFactory;

    public function __construct(
        VendorFactory $vendorFactory
    ) {
        $this->vendorFactory = $vendorFactory;
    }

    public function deleteById($vendorId)
    {
        $vendorModel = $this->vendorFactory->create()->load($vendorId);
        $vendorModel->delete();
        return true;
    }
}