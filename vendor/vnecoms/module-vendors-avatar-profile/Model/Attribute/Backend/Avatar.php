<?php
/**
 * Created by PhpStorm.
 * User: camph
 * Date: 06/12/2018
 * Time: 10:26
 */
namespace Vnecoms\VendorsAvatarProfile\Model\Attribute\Backend;

use Vnecoms\VendorsAvatarProfile\Model\Source\Validation\Image;

class Avatar extends \Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend
{
    /**
     * @param \Magento\Framework\DataObject $object
     * @return \Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeSave($object)
    {
        $validation = new Image();
        $attrCode = $this->getAttribute()->getAttributeCode();
        if ($attrCode == 'profile_picture') {
            if ($validation->isImageValid('tmp_name', $attrCode) === false) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('The profile picture is not a valid image.')
                );
            }
        }
        return parent::beforeSave($object);
    }
}
