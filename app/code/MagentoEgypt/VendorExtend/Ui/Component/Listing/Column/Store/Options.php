<?php 
namespace MagentoEgypt\VendorExtend\Ui\Component\Listing\Column\Store;

class Options extends \Magento\Store\Ui\Component\Listing\Column\Store\Options
{
    /**
     * Sanitize website/store option name
     *
     * @param string $name
     *
     * @return string
     */
    protected function sanitizeName($name)
    {
        $matches = [];
        preg_match('/\$[:]*{(.)*}/', $name ?: '', $matches);
        if (count($matches) > 0) {
            $name = $this->escaper->escapeHtml($this->escaper->escapeJs($name));
        } else {
            $name = $this->escaper->escapeHtml($name);
        }

        return __($name);
    }
}