<?php 
namespace MagentoEgypt\VendorExtend\Plugin\Helper\VendorsProduct;

use \Vnecoms\VendorsProduct\Model\Source\Approval;

class Data
{
    public function afterGetAllowedApprovalStatus(\Vnecoms\VendorsProduct\Helper\Data $subject, array $return) {
        return [
            Approval::STATUS_APPROVED
        ];
    }
}