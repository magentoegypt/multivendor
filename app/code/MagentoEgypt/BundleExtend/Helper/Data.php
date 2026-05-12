<?php
namespace MagentoEgypt\BundleExtend\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    public const NEW_BUNDLE_TYPE_CODE = 'new_bundle';

    protected $skipComplexCheck = false;
    protected $skipRequiredCheck = false;
    protected $skipConfigValidation = false;
    protected $overrideTypeIdAsBundle = false;

    public function setSkipComplexCheck($skipComplexCheck)
    {
        $this->skipComplexCheck = $skipComplexCheck;
    }

    public function getSkipComplexCheck()
    {
        return $this->skipComplexCheck;
    }

    public function setSkipRequiredCheck($skipRequiredCheck)
    {
        $this->skipRequiredCheck = $skipRequiredCheck;
    }

    public function getSkipRequiredCheck()
    {
        return $this->skipRequiredCheck;
    }

    public function setSkipConfig($skipConfigValidation)
    {
        $this->skipConfigValidation = $skipConfigValidation;
    }

    public function getSkipConfig()
    {
        return $this->skipConfigValidation;
    }

    public function setOverrideTypeIdAsBundle($flag)
    {
        $this->overrideTypeIdAsBundle = (bool)$flag;
    }

    public function getOverrideTypeIdAsBundle()
    {
        return $this->overrideTypeIdAsBundle;
    }
}
