<?php
namespace Vnecoms\VendorsTranslateInline\Plugin\Translate;

use Magento\Framework\App\ScopeInterface;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Translate\Inline\ConfigInterface;
use Magento\Framework\Translate\Inline\StateInterface;

class Inline
{
    /**
     * @var array
     */
    private $allowedAreas = ["vendors"];

    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @var
     */
    protected $isAllowed;

    /**
     * @var StateInterface
     */
    protected $state;

    /**
     * @var ScopeResolverInterface
     */
    protected $scopeResolver;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * Inline constructor.
     * @param ScopeResolverInterface $scopeResolver
     * @param StateInterface $state
     * @param ConfigInterface $config
     * @param \Magento\Framework\App\State $appState
     */
    public function __construct(
        ScopeResolverInterface $scopeResolver,
        StateInterface $state,
        ConfigInterface $config,
        \Magento\Framework\App\State $appState
    ) {
        $this->appState = $appState;
        $this->scopeResolver = $scopeResolver;
        $this->config = $config;
        $this->state = $state;
    }

    /**
     * @param \Magento\Framework\Translate\Inline $subject
     * @param $result
     */
    public function afterIsAllowed(
        \Magento\Framework\Translate\Inline $subject,
        $result
    ) {
        $isVendors = $this->isAreaAllowed();
        if ($isVendors) {
            $scope = $this->scopeResolver->getScope();
            $this->isAllowed = $this->config->isActive($scope)
                && $this->config->isDevAllowed($scope);
            return $this->state->isEnabled() && $this->isAllowed;
        }
        return $result;
    }

    /**
     * Indicates whether the current area is valid for inline translation
     *
     * @return bool
     */
    private function isAreaAllowed(): bool
    {
        try {
            return in_array($this->appState->getAreaCode(), $this->allowedAreas, true);
        } catch (LocalizedException $e) {
            return false;
        }
    }
}
