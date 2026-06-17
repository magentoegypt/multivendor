<?php
namespace Vnecoms\Vendors\Plugin\App;

use Magento\Framework\App\Request\Http as RequestHttp;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Framework\App\Config\ScopeConfigInterface;


class AreaList
{
    const XML_PATH_USE_CUSTOM_VENDOR_URL = 'vendors/url/use_custom';

    const XML_PATH_CUSTOM_VENDOR_URL = 'vendors/url/custom';

    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    protected $request;

    /*x
     * @var \Magento\Backend\App\ConfigInterface
     */
    protected $config;


    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /**
     * @var array
     */
    protected $standardPorts = ['http' => '80', 'https' => '443'];

    /**
     * @param RequestHttp $request
     */
    public function __construct(
        RequestHttp $request,
        \Magento\Backend\App\Config $config,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->request = $request;
        $this->config = $config;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param \Magento\Framework\App\AreaList $subject
     * @param $frontName
     * @return array
     */
    public function beforeGetCodeByFrontName(
        \Magento\Framework\App\AreaList $subject,
                                        $frontName
    ) {
        if ($frontName) return [$frontName];
        if ($this->isHostVendorBackend()) {
              return ["vendors"];
        }
        return [$frontName];
    }

    /**
     * Return whether the host from request is the vendor host
     *
     * @return bool
     */
    public function isHostVendorBackend()
    {
        $vendorUrl = false;
        if ($this->scopeConfig->getValue(self::XML_PATH_USE_CUSTOM_VENDOR_URL, ScopeInterface::SCOPE_STORE)) {
            $vendorUrl = $this->scopeConfig->getValue(self::XML_PATH_CUSTOM_VENDOR_URL, ScopeInterface::SCOPE_STORE);
        }

        if (!$vendorUrl) return false;

        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        return $this->getHostNameWithPort($vendorUrl) === $host;
    }

    /**
     * Get host with port
     *
     * @param string $url
     * @return mixed|string
     */
    private function getHostNameWithPort($vendorUrl)
    {
        #$schemeVar = parse_url(trim($vendorUrl), PHP_URL_SCHEME);
        return parse_url(trim($vendorUrl), PHP_URL_HOST);
    }
}
