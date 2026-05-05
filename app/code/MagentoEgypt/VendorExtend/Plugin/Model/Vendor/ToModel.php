<?php
namespace MagentoEgypt\VendorExtend\Plugin\Model\Vendor;

use Vnecoms\Vendors\Model\Vendor;

class ToModel
{
    protected $vendorHelper;
    public function __construct(\Vnecoms\Vendors\Helper\Data $vendorHelper)
    {
        $this->vendorHelper = $vendorHelper;
    }

    public function afterToModel($subject, Vendor $dataModel)
    {
        if (!$dataModel->getId() && $this->vendorHelper->isRequiredVendorApproval()){
            $dataModel->setStatus(Vendor::STATUS_PENDING);
        }
        return $dataModel;
    }
}
