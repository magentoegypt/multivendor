<?php
declare(strict_types=1);
namespace MagentoEgypt\CityManager\Model;
class Settings
{
    public function __construct(private \Magento\Framework\App\Config\ScopeConfigInterface $config) {}
    public function enabled(): bool {return $this->config->isSetFlag('citymanager/general/enabled',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);}
}
