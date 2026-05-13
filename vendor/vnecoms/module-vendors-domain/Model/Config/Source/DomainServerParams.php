<?php
namespace Vnecoms\VendorsDomain\Model\Config\Source;

class DomainServerParams extends \Magento\Framework\DataObject implements \Magento\Framework\Option\ArrayInterface
{
    const SERVER_NAME   = 'SERVER_NAME';
    const HTTP_HOST     = 'HTTP_HOST';
    const CUSTOM_PARAM  = 'custom';
    /**
     * Generate list of email templates
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            ['value' => self::SERVER_NAME,'label' => __("SERVER_NAME")],
            ['value' => self::HTTP_HOST,'label' => __("HTTP_HOST")],
            ['value' => self::CUSTOM_PARAM,'label' => __("Custom Param")],
        ];
        
        return $options;
    }
}
