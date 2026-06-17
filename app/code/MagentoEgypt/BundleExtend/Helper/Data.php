<?php
namespace MagentoEgypt\BundleExtend\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    public const NEW_BUNDLE_TYPE_CODE = 'new_bundle';

    protected $skipRequiredCheck = false;
    protected $skipConfigValidation = false;

    /**
     * Reentrant stacks for the two flags that can be set by nested scopes
     * (e.g. SaveHandlerWrapper wraps the whole save, then LinkManagement::saveChild
     * sets them again for one child). A plain boolean with set(true)/set(false) is
     * NOT nesting-safe: the inner scope's set(false) clobbers the outer scope's
     * set(true), so anything the outer scope still has to do (e.g. addChildren())
     * suddenly sees the real 'new_bundle' type again and the core bundle guard throws
     * "isn't a bundle product". Using a push/pop stack means an inner pop only
     * restores the value the outer scope already had, never forcing it off.
     *
     * @var bool[]
     */
    private $skipComplexCheckStack = [];

    /** @var bool[] */
    private $overrideTypeIdStack = [];

    public function pushSkipComplexCheck(bool $value): void
    {
        $this->skipComplexCheckStack[] = $value;
    }

    public function popSkipComplexCheck(): void
    {
        array_pop($this->skipComplexCheckStack);
    }

    public function getSkipComplexCheck()
    {
        return !empty($this->skipComplexCheckStack) && end($this->skipComplexCheckStack);
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

    public function pushOverrideTypeIdAsBundle(bool $value): void
    {
        $this->overrideTypeIdStack[] = $value;
    }

    public function popOverrideTypeIdAsBundle(): void
    {
        array_pop($this->overrideTypeIdStack);
    }

    public function getOverrideTypeIdAsBundle()
    {
        return !empty($this->overrideTypeIdStack) && end($this->overrideTypeIdStack);
    }
}
