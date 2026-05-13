<?php
/**
 * Widgets Insertion Plugin Config for Editor HTML Element.
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model\Wysiwyg\App;

class Config
{
    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $_assetRepo;

    /**
     * @var \Vnecoms\VendorsCms\Model\App
     */
    protected $_app;

    /**
     * @var \Vnecoms\Vendors\Model\UrlInterface
     */
    protected $_vendorUrl;

    /**
     * @var \Magento\Framework\Url\DecoderInterface
     */
    protected $urlDecoder;

    /**
     * @var \Vnecoms\VendorsCms\Model\AppFactory
     */
    protected $_appFactory;

    /**
     * @var \Magento\Framework\Url\EncoderInterface
     */
    protected $urlEncoder;

    public function __construct(
        \Vnecoms\Vendors\Model\UrlInterface $vendorUrl,
        \Magento\Framework\Url\DecoderInterface $urlDecoder,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Vnecoms\VendorsCms\Model\AppFactory $appFactory,
        \Magento\Framework\Url\EncoderInterface $urlEncoder
    ) {
        $this->_vendorUrl = $vendorUrl;
        $this->urlDecoder = $urlDecoder;
        $this->_assetRepo = $assetRepo;
        $this->_appFactory = $appFactory;
        $this->urlEncoder = $urlEncoder;
    }

    /**
     * Return config settings for widgets insertion plugin based on editor element config.
     *
     * @param \Magento\Framework\DataObject $config
     *
     * @return array
     */
    public function getPluginSettings($config)
    {
        $url = $this->_assetRepo->getUrl(
            'Vnecoms_VendorsCms::js/lib/wysiwyg/tiny_mce/plugins/app_plugin/app_plugin.js'
        );
        $settings = [
            'app_plugin_src' => $url,
            'app_placeholders' => $this->_appFactory->create()->getPlaceholderImageUrls(),
            'app_window_url' => $this->getAppWindowUrl($config),
        ];

        return $settings;
    }

    /**
     * Return Application Insertion Plugin Window URL.
     *
     * @param \Magento\Framework\DataObject $config Editor element config
     *
     * @return string
     */
    public function getAppWindowUrl($config)
    {
        $params = [];

        $skipped = is_array($config->getData('skip_apps')) ? $config->getData('skip_apps') : [];
        if ($config->hasData('app_filters')) {
            $all = $this->_appFactory->create()->getWidgets();
            $filtered = $this->_appFactory->create()->getWidgets($config->getData('app_filters'));
            foreach ($all as $code => $widget) {
                if (!isset($filtered[$code])) {
                    $skipped[] = $widget['@']['type'];
                }
            }
        }

        if (count($skipped) > 0) {
            $params['skip_widgets'] = $this->encodeWidgetsToQuery($skipped);
        }

        return $this->_vendorUrl->getUrl('cms/app_tool/index', $params);
    }

    /**
     * Encode list of widget types into query param.
     *
     * @param string[]|string $widgets List of widgets
     *
     * @return string Query param value
     */
    public function encodeAppsToQuery($widgets)
    {
        $widgets = is_array($widgets) ? $widgets : [$widgets];
        $param = implode(',', $widgets);

        return $this->urlEncoder->encode($param);
    }

    /**
     * Decode URL query param and return list of widgets.
     *
     * @param string $queryParam Query param value to decode
     *
     * @return string[] Array of widget types
     */
    public function decodeAppsFromQuery($queryParam)
    {
        $param = $this->urlDecoder->decode($queryParam);

        return preg_split('/\s*\,\s*/', $param, 0, PREG_SPLIT_NO_EMPTY);
    }
}
