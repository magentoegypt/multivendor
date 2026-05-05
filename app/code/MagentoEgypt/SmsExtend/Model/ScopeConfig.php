<?php
namespace MagentoEgypt\SmsExtend\Model;

use Magento\Framework\App\Config\ConfigTypeInterface;
use Magento\Framework\App\Config\ScopeCodeResolver;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;

class ScopeConfig implements ScopeConfigInterface
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ScopeCodeResolver
     */
    protected $scopeCodeResolver;

    /**
     * @var ConfigTypeInterface[]
     */
    protected $types;

    /**
     * Config constructor.
     *
     * @param ScopeCodeResolver $scopeCodeResolver
     * @param StoreManagerInterface $storeManager
     * @param array $types
     */
    public function __construct(
        ScopeCodeResolver $scopeCodeResolver,
        StoreManagerInterface $storeManager,
        array $types = []
    ) {
        $this->scopeCodeResolver = $scopeCodeResolver;
        $this->types = $types;
        $this->storeManager = $storeManager;
    }

    /**
     * Retrieve config value by path and scope
     *
     * @param string $path
     * @param string $scopeType
     * @param null|int|string $scopeCode
     * @return mixed
     */
    public function getValue(
        $path = null,
        $scope = ScopeConfigInterface::SCOPE_STORE,
        $scopeCode = null
    ) {
        if(empty($scopeCode)) {
            $scopeCode = $this->storeManager->getStore()->getId();
        }
        return $this->getConfigValue($path, $scope, $scopeCode);
    }

    /**
     * Retrieve config value by path and scope
     *
     * @param string $path
     * @param string $scope
     * @param null|int|string $scopeCode
     * @return mixed
     */
    public function getConfigValue(
        $path = null,
        $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        $scopeCode = null
    ) {
        if ($scope === 'store') {
            $scope = 'stores';
        } elseif ($scope === 'website') {
            $scope = 'websites';
        }
        $configPath = $scope;
        if ($scope !== 'default') {
            if (is_numeric($scopeCode) || $scopeCode === null) {
                $scopeCode = $this->scopeCodeResolver->resolve($scope, $scopeCode);
            } elseif ($scopeCode instanceof \Magento\Framework\App\ScopeInterface) {
                $scopeCode = $scopeCode->getCode();
            }
            if ($scopeCode) {
                $configPath .= '/' . $scopeCode;
            }
        }
        if ($path) {
            $configPath .= '/' . $path;
        }
        return $this->get('system', $configPath);
    }

    /**
     * Retrieve config flag
     *
     * @param string $path
     * @param string $scope
     * @param null|int|string $scopeCode
     * @return bool
     */
    public function isSetFlag($path, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null)
    {
        return !!$this->getValue($path, $scope, $scopeCode);
    }

    /**
     * Invalidate cache by type
     *
     * Clean scopeCodeResolver
     *
     * @return void
     */
    public function clean()
    {
        foreach ($this->types as $type) {
            $type->clean();
        }
        $this->scopeCodeResolver->clean();
    }

    /**
     * Retrieve configuration.
     *
     * ('modules') - modules status configuration data
     * ('scopes', 'websites/base') - base website data
     * ('scopes', 'stores/default') - default store data
     *
     * ('system', 'default/web/seo/use_rewrites') - default system configuration data
     * ('system', 'websites/base/web/seo/use_rewrites') - 'base' website system configuration data
     *
     * ('i18n', 'default/en_US') - translations for default store and 'en_US' locale
     *
     * @param string $configType
     * @param string|null $path
     * @param mixed|null $default
     * @return array
     */
    public function get($configType, $path = '', $default = null)
    {
        $result = null;
        if (isset($this->types[$configType])) {
            $result = $this->types[$configType]->get($path);
        }

        return $result !== null ? $result : $default;
    }
}
