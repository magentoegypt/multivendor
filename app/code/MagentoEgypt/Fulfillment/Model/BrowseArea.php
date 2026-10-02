<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Model;

final class BrowseArea
{
    public function __construct(private \Magento\Framework\App\RequestInterface $request,
        private \Magento\Framework\App\Config\ScopeConfigInterface $config) {}
    public function get(): ?array
    {
        if (!$this->config->isSetFlag('hubfulfillment/general/catalog_enabled')) return null;
        $country=$this->request->getHeader('X-Hub-Country') ?: $this->request->getParam('hf_country');
        if (!$country) return null;
        if (!is_string($country) || !in_array($country,['EG','SA','AE','US'],true)) throw new \Magento\Framework\Exception\InputException(__('Invalid delivery country.'));
        $area=[$country];
        foreach (['region','city','locality'] as $key) {
            $value=$this->request->getHeader('X-Hub-'.ucfirst($key)) ?: $this->request->getParam('hf_'.$key,'0');
            if (!is_scalar($value) || !preg_match('/^\d{1,10}$/D',(string)$value) || (int)$value>2147483647) throw new \Magento\Framework\Exception\InputException(__('Invalid delivery area.'));
            $area[]=(int)$value;
        }
        return $area;
    }
}
