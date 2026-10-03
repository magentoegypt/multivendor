<?php
namespace MagentoEgypt\CityManager\Block;
class Bootstrap extends \Magento\Framework\View\Element\Template
{
    protected function _toHtml() {
        if (!$this->_scopeConfig->isSetFlag('citymanager/general/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) return '';
        return parent::_toHtml();
    }
    public function config(): string {
        $base=$this->_storeManager->getStore()->getBaseUrl();
        return json_encode(['endpoint'=>$base.'citymanager/directory/index','countries'=>['EG','SA','AE','US']],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
    }
}
