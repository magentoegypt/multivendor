<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Observer\OrderAttribute;

use Amasty\CheckoutCore\Cache\InvalidateCheckoutCache;
use Amasty\Orderattr\Model\Attribute\Attribute;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class InvalidateCache implements ObserverInterface
{
    public const FLAG_NO_INVALIDATE = 'no_invalidate';

    /**
     * @var InvalidateCheckoutCache
     */
    private $invalidateCheckoutCache;

    public function __construct(InvalidateCheckoutCache $invalidateCheckoutCache)
    {
        $this->invalidateCheckoutCache = $invalidateCheckoutCache;
    }

    /**
     * Event: amasty_orderattr_entity_attribute_save_after, amasty_orderattr_entity_attribute_delete_after
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var Attribute $attribute */
        $attribute = $observer->getEvent()->getData('attribute');

        if (!$attribute->hasData(self::FLAG_NO_INVALIDATE)) {
            $this->invalidateCheckoutCache->execute();
        }
    }
}
