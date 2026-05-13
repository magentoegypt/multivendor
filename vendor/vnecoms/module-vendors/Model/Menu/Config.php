<?php
namespace Vnecoms\Vendors\Model\Menu;

use Magento\Backend\Model\Menu\Builder;

class Config extends \Magento\Backend\Model\Menu\Config
{
    const CACHE_ID = 'vendor_menu_config';
    const CACHE_VENDOR_MENU_OBJECT = 'vendor_menu_object';

    /**
     * @var Builder
     */
    private $_menuBuilder;

    /**
     * @param \Magento\Backend\Model\Menu\Builder $menuBuilder
     * @param \Magento\Backend\Model\Menu\AbstractDirector $menuDirector
     * @param \Magento\Backend\Model\MenuFactory $menuFactory
     * @param \Magento\Backend\Model\Menu\Config\Reader $configReader
     * @param \Magento\Framework\App\Cache\Type\Config $configCacheType
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\State $appState
     */
    public function __construct(
        \Magento\Backend\Model\Menu\Builder $menuBuilder,
        \Magento\Backend\Model\Menu\AbstractDirector $menuDirector,
        \Magento\Backend\Model\MenuFactory $menuFactory,
        \Magento\Backend\Model\Menu\Config\Reader $configReader,
        \Magento\Framework\App\Cache\Type\Config $configCacheType,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\State $appState
    ) {
        $this->_menuBuilder = $menuBuilder;
        parent::__construct(
            $menuBuilder,
            $menuDirector,
            $menuFactory,
            $configReader,
            $configCacheType,
            $eventManager ,
            $logger,
            $scopeConfig,
            $appState
        );
    }

    /**
     * (non-PHPdoc)
     * @see \Magento\Backend\Model\Menu\Config::_initMenu()
     */
    protected function _initMenu()
    {
        if (!$this->_menu) {
            $menu = $this->_menuFactory->create();

            $cache = $this->_configCacheType->load(self::CACHE_VENDOR_MENU_OBJECT);
            if ($cache) {
                $menu->unserialize($cache);
                $this->_menu = $menu;
                return;
            }

            $areaCode = $this->_appState->getAreaCode();
            $this->_director->direct(
                $this->_configReader->read($areaCode),
                $this->_menuBuilder,
                $this->_logger
            );
            $menu = $this->_menuBuilder->getResult($menu);
            $this->_menu = $menu;
            $this->_configCacheType->save($menu->serialize(), self::CACHE_VENDOR_MENU_OBJECT);
        }
    }
}
