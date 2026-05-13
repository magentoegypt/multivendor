<?php
/**
 * Created by PhpStorm.
 * User: camph
 * Date: 06/12/2018
 * Time: 10:29
 */

namespace Vnecoms\VendorsAvatarProfile\Model\Source\Validation;

class Image
{
    /**
     * @param $tmp_name
     * @param $attrCode
     * @return bool
     */
    public function isImageValid($tmp_name, $attrCode)
    {
        if ($attrCode == 'profile_picture') {
            // phpcs:ignore Magento2.Security.Superglobal.SuperglobalUsageError
            if (!empty($_FILES[$attrCode][$tmp_name])) {
                // phpcs:ignore Magento2.Security.Superglobal.SuperglobalUsageError
                $imageFile = getimagesize($_FILES[$attrCode][$tmp_name]);
                /** @codingStandardsIgnoreEnd */
                if ($imageFile === false) {
                    return false;
                } elseif ($imageFile === true) {
                    $valid_types = ['image/gif', 'image/jpeg', 'image/png'];
                    if (!in_array($imageFile['mime'], $valid_types)) {
                        return false;
                    }
                }
                return true;
            }
        }
        return true;
    }
}
