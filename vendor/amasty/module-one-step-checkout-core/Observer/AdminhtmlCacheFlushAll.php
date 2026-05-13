<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Observer;

use Amasty\CheckoutCore\Model\Optimization\DeleteCheckoutBundles;
use Magento\Framework\Event\ObserverInterface;

/**
 * Delete js bundle file while cache flush.
 *
 * scope: global
 * event name: adminhtml_cache_flush_all
 * observer name: Amasty_CheckoutCore::delete_bundle
 */
class AdminhtmlCacheFlushAll implements ObserverInterface
{
    /**
     * @var DeleteCheckoutBundles
     */
    private $deleteMergedJs;

    public function __construct(DeleteCheckoutBundles $deleteMergedJs)
    {
        $this->deleteMergedJs = $deleteMergedJs;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->deleteMergedJs->execute();
    }
}
