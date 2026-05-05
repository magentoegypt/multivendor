<?php
namespace MagentoEgypt\SmsExtend\Plugin;

use Vnecoms\VendorsApi\Model\Data\Vendor\ToModel;
use Vnecoms\VendorsApi\Model\Data\Vendor as VendorDataModel;

class VendorToModel
{
    public function beforeToModel(ToModel $subject, VendorDataModel $dataModel)
    {
        $telephone = $dataModel->getTelephone();
        if(!empty($telephone)) {
            $extensionAttributes = $dataModel->getExtensionAttributes();
            $extensionAttributes->setData('mobilenumber',$telephone);
            $dataModel->setExtensionAttributes($extensionAttributes);
        }
        return [$dataModel];
    }
}