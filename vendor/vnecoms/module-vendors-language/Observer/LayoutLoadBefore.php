<?php
namespace Vnecoms\VendorsLanguage\Observer;

use Magento\Framework\Event\ObserverInterface;

class LayoutLoadBefore implements ObserverInterface
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $localeResolver;


    /**
     * LayoutLoadBefore constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Locale\ResolverInterface $localeResolver
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->localeResolver = $localeResolver;
    }

    /**
     * Add the notification if there are any vendor awaiting for approval.
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $layout = $observer->getEvent()->getData('layout');
        $rtlLanguages = $this->scopeConfig->getValue('vendors/design/rtl_language');
        if (!$rtlLanguages) return;
        $rtlLanguages = explode(",", $rtlLanguages);
        $currentLocale = $this->localeResolver->getLocale();
        if(!in_array($currentLocale, $rtlLanguages)) return;

        $layout->getUpdate()->addHandle('vendors_panel_layout_rtl');
    }
}
