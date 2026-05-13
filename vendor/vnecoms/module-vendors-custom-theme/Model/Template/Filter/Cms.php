<?php
namespace Vnecoms\VendorsCustomTheme\Model\Template\Filter;

class Cms extends \Magento\Cms\Model\Template\Filter
{
    /**
     * Retrieve media file URL directive
     *
     * @param string[] $construction
     * @return string
     */
    public function mediaDirective($construction)
    {
        $params = $this->getParameters(html_entity_decode($construction[2], ENT_QUOTES));
        if (preg_match('/\.\.(\\\|\/)/', $params['url'])) {
            throw new \InvalidArgumentException('Image path must be absolute');
        }
        
        return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . $params['url'];
    }
}
