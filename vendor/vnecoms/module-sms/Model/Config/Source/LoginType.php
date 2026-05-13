<?php
namespace Vnecoms\Sms\Model\Config\Source;

class LoginType extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{

    const TYPE_NO = '0';
    const TYPE_2FA    = '1';
    const TYPE_OTP      = '2';

    /**
     * Options array
     *
     * @var array
     */
    protected $_options = null;

    /**
     * Retrieve all options array
     *
     * @return array
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ['label' => __('No'), 'value' => self::TYPE_NO],
                ['label' => __('2FA'), 'value' => self::TYPE_2FA],
                ['label' => __('2FA and OTP'), 'value' => self::TYPE_OTP],
            ];
        }
        return $this->_options;
    }

    /**
     * Retrieve option array
     *
     * @return array
     */
    public function getOptionArray()
    {
        $_options = [];
        foreach ($this->getAllOptions() as $option) {
            $_options[$option['value']] = $option['label'];
        }
        return $_options;
    }


    /**
     * Get options as array
     *
     * @return array
     * @codeCoverageIgnore
     */
    public function toOptionArray()
    {
        return $this->getAllOptions();
    }
}
