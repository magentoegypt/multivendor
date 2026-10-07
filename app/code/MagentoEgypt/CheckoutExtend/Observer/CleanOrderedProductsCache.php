<?php
/**
 * When an order reserves or releases stock, the pages showing those products are refreshed.
 *
 * Companion to Plugin\Catalog\ReservedStockAvailability ([CL036-TC95]). The
 * storefront now shows a product out of stock once open orders reserve its last
 * units, but placing an order only writes a reservation: MSI cleans product cache
 * tags when the stock INDEX changes, and a reservation does not touch the index.
 * Without this, the product page and the category pages listing it would stay in
 * Varnish/FPC with "In Stock" and an Add to Cart button until something else
 * purged them.
 *
 * Runs after a successful order submit (storefront, admin, REST and GraphQL all
 * go through QuoteManagement) and after an order cancel (which compensates the
 * reservation). It cleans the ordered products' cat_p_<id> tags — including the
 * configurable/bundle parent rows — which is what Magento's own product save does.
 */
declare(strict_types=1);

namespace MagentoEgypt\CheckoutExtend\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Indexer\CacheContextFactory;
use Psr\Log\LoggerInterface;

class CleanOrderedProductsCache implements ObserverInterface
{
    public function __construct(
        private readonly CacheContextFactory $cacheContextFactory,
        private readonly EventManager $eventManager,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(Observer $observer): void
    {
        try {
            /** @var \Magento\Sales\Model\Order|null $order */
            $order = $observer->getEvent()->getOrder();
            if (!$order) {
                return;
            }

            $ids = [];
            foreach ($order->getAllItems() as $item) {
                if ((int) $item->getProductId() > 0) {
                    $ids[(int) $item->getProductId()] = true;
                }
            }
            if (!$ids) {
                return;
            }

            $context = $this->cacheContextFactory->create();
            $context->registerEntities(Product::CACHE_TAG, array_keys($ids));
            $this->eventManager->dispatch('clean_cache_by_tags', ['object' => $context]);
        } catch (\Throwable $e) {
            // Never let a cache clean get in the way of an order.
            $this->logger->warning('CleanOrderedProductsCache: ' . $e->getMessage());
        }
    }
}
