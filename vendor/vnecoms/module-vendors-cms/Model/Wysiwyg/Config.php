<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model\Wysiwyg;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Ui\Component\Wysiwyg\ConfigInterface;
use Magento\Framework\App\ObjectManager;

/**
 * Wysiwyg Config for Editor HTML Element.
 */
class Config extends \Magento\Framework\DataObject implements ConfigInterface
{
    /**
     * Wysiwyg status enabled.
     */
    const WYSIWYG_ENABLED = 'enabled';

    /**
     * Wysiwyg status configuration path.
     */
    const WYSIWYG_STATUS_CONFIG_PATH = 'cms/wysiwyg/enabled';

    /**
     * Wysiwyg status hidden.
     */
    const WYSIWYG_HIDDEN = 'hidden';

    /**
     * Wysiwyg status disabled.
     */
    const WYSIWYG_DISABLED = 'disabled';

    /**
     * Wysiwyg image directory.
     */
    const IMAGE_DIRECTORY = 'wysiwyg';

    const IMAGE_VENDORSCMS_DIRECTORY = 'vendorscms';

    /**
     * @var \Magento\Framework\AuthorizationInterface
     */
    protected $_authorization;

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $_assetRepo;

    /**
     * @var \Magento\Variable\Model\Variable\Config
     */
    protected $_variableConfig;

    /**
     * @var \Magento\Widget\Model\Widget\Config
     */
    protected $_widgetConfig;

    /**
     * Core event manager proxy.
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager;

    /**
     * Core store config.
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $_backendUrl;

    /**
     * @var array
     */
    protected $_windowSize;

    /** @var  \Vnecoms\Vendors\Model\UrlInterface */
    protected $_vendorUrl;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /** @var \Vnecoms\VendorsCms\Model\Block\Config  */
    protected $_blockConfig;

    protected $_appConfig;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var \Magento\Cms\Model\Wysiwyg\CompositeConfigProvider
     */
    private $configProvider;

    /**
     * Config constructor.
     * @param \Magento\Backend\Model\UrlInterface $backendUrl
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Framework\AuthorizationInterface $authorization
     * @param \Magento\Framework\View\Asset\Repository $assetRepo
     * @param \Magento\Variable\Model\Variable\Config $variableConfig
     * @param \Magento\Widget\Model\Widget\Config $widgetConfig
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Vnecoms\VendorsCms\Model\Block\Config $blockConfig
     * @param App\Config $appConfig
     * @param \Vnecoms\Vendors\Model\UrlInterface $vendorUrl
     * @param Filesystem $filesystem
     * @param array $windowSize
     * @param array $data
     * @param \Magento\Cms\Model\Wysiwyg\CompositeConfigProvider|null $configProvider
     */
    public function __construct(
        \Magento\Backend\Model\UrlInterface $backendUrl,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\AuthorizationInterface $authorization,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Variable\Model\Variable\Config $variableConfig,
        \Magento\Widget\Model\Widget\Config $widgetConfig,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Vnecoms\VendorsCms\Model\Block\Config $blockConfig,
        \Vnecoms\VendorsCms\Model\Wysiwyg\App\Config $appConfig,
        \Vnecoms\Vendors\Model\UrlInterface $vendorUrl,
        Filesystem $filesystem,
        array $windowSize = [],
        array $data = [],
        \Magento\Cms\Model\Wysiwyg\CompositeConfigProvider $configProvider = null
    ) {
        $this->_backendUrl = $backendUrl;
        $this->_eventManager = $eventManager;
        $this->_scopeConfig = $scopeConfig;
        $this->_authorization = $authorization;
        $this->_assetRepo = $assetRepo;
        $this->_variableConfig = $variableConfig;
        $this->_widgetConfig = $widgetConfig;
        $this->_blockConfig = $blockConfig;
        $this->_appConfig = $appConfig;
        $this->_windowSize = $windowSize;
        $this->_storeManager = $storeManager;
        $this->_vendorUrl = $vendorUrl;
        $this->filesystem = $filesystem;
        $this->configProvider = $configProvider ?: ObjectManager::getInstance()
            ->get(\Magento\Cms\Model\Wysiwyg\CompositeConfigProvider ::class);
        parent::__construct($data);
    }

    /**
     * Return Wysiwyg config as \Magento\Framework\DataObject.
     *
     * Config options description:
     *
     * enabled:                 Enabled Visual Editor or not
     * hidden:                  Show Visual Editor on page load or not
     * use_container:           Wrap Editor contents into div or not
     * no_display:              Hide Editor container or not (related to use_container)
     * translator:              Helper to translate phrases in lib
     * files_browser_*:         Files Browser (media, images) settings
     * encode_directives:       Encode template directives with JS or not
     *
     * @param array|\Magento\Framework\DataObject $data Object constructor params to override default config values
     *
     * @return \Magento\Framework\DataObject
     */
    public function getConfig($data = [])
    {
        $config = new \Magento\Framework\DataObject();

        $config->setData(
            [
                'enabled' => $this->isEnabled(),
                'hidden' => $this->isHidden(),
                'use_container' => false,
                'add_variables' => false,
                'add_widgets' => false,
                'add_blocks' => true,
                'add_apps' => false,
                'add_images' => false,
                'add_directives' => true,
                'no_display' => false,
                'encode_directives' => true,
                'baseStaticUrl' => $this->_assetRepo->getStaticViewFileContext()->getBaseUrl(),
                'baseStaticDefaultUrl' => str_replace('index.php/', '', $this->_backendUrl->getBaseUrl())
                    .$this->filesystem->getUri(DirectoryList::STATIC_VIEW).'/',
                'directives_url' => $this->_vendorUrl->getUrl('cms/wysiwyg/directive'),
                'width' => '98%',
                'height' => '500px',
                'plugins' => [],
            ]
        );

        $config->setData('directives_url_quoted', preg_quote($config->getData('directives_url')));

        if (is_array($data)) {
            $config->addData($data);
        }

        if ($config->getData('add_variables')) {
            $this->configProvider->processVariableConfig($config);
        }

        if ($config->getData('add_widgets')) {
            $this->configProvider->processWidgetConfig($config);
        }

        if ($config->getData('add_blocks')) {
            $settings = $this->_blockConfig->getBlockPluginSettings($config);
            $config->addData($settings);
        }

        if ($config->getData('add_apps')) {
            $settings = $this->_appConfig->getPluginSettings($config);
            $config->addData($settings);
        }

        return $this->configProvider->processWysiwygConfig($config);
    }


    /**
     * Return path for skin images placeholder.
     *
     * @return string
     */
    public function getSkinImagePlaceholderPath()
    {
        $staticPath = $this->_storeManager->getStore()->getBaseStaticDir();
        $placeholderPath = $this->_assetRepo->createAsset('Vnecoms_VendorsCms::images/wysiwyg_skin_image.png')->getPath();

        return $staticPath.'/'.$placeholderPath;
    }

    /**
     * Check whether Wysiwyg is enabled or not.
     *
     * @return bool
     */
    public function isEnabled()
    {
        $wysiwygState = $this->_scopeConfig->getValue(
            self::WYSIWYG_STATUS_CONFIG_PATH,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->getStoreId()
        );

        return in_array($wysiwygState, [self::WYSIWYG_ENABLED, self::WYSIWYG_HIDDEN]);
    }

    /**
     * Check whether Wysiwyg is loaded on demand or not.
     *
     * @return bool
     */
    public function isHidden()
    {
        $status = $this->_scopeConfig->getValue(
            self::WYSIWYG_STATUS_CONFIG_PATH,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        return $status == self::WYSIWYG_HIDDEN;
    }
}
