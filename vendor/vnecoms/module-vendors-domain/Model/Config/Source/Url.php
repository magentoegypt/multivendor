<?php
namespace Vnecoms\VendorsDomain\Model\Config\Source;

class Url extends \Magento\Framework\DataObject implements \Magento\Framework\Option\ArrayInterface
{
    const URL_SUB_FOLDER    = 'sub_folder';
    const URL_SUB_DOMAIN    = 'sub_domain';
    const URL_DOMAIN        = 'domain';
    
    /**
     * Generate list of email templates
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            ['value' => self::URL_SUB_FOLDER,'label' => __("Sub folder")],
            ['value' => self::URL_SUB_DOMAIN,'label' => __("Sub Domain")],
            ['value' => self::URL_DOMAIN,'label' => __("Your own domain")],
        ];
        
        return $options;
    }
}
