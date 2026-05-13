<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Cache;

use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\App\Cache\Type\FrontendPool;

/**
 * One Step Checkout cache type class
 */
class Type extends \Magento\Framework\Cache\Frontend\Decorator\TagScope
{
    /**
     * Cache type code unique among all cache types
     */
    public const TYPE_IDENTIFIER = 'checkout';

    /**
     * Cache tag used to distinguish the cache type from all other cache
     */
    public const CACHE_TAG = 'AMASTY_CHECKOUT';

    /**
     * @var StateInterface
     */
    private $cacheState;

    public function __construct(
        FrontendPool $cacheFrontendPool,
        StateInterface $cacheState
    ) {
        parent::__construct($cacheFrontendPool->get(self::TYPE_IDENTIFIER), self::CACHE_TAG);
        $this->cacheState = $cacheState;
    }

    /**
     * load cache record is exist by cache key
     *
     * @param string $identifier
     *
     * @return bool|string
     */
    public function load($identifier)
    {
        if (!$this->isEnabled()) {
            return false;
        }

        return parent::load($identifier);
    }

    /**
     * Save cache record
     *
     * @param string $data
     * @param string $identifier
     * @param array $tags
     * @param int|bool|null $lifeTime
     * @return bool
     */
    public function save($data, $identifier, array $tags = [], $lifeTime = null)
    {
        if (!$this->isEnabled()) {
            return false;
        }

        return parent::save($data, $identifier, $tags, $lifeTime);
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->cacheState->isEnabled(\Amasty\CheckoutCore\Cache\Type::TYPE_IDENTIFIER);
    }
}
