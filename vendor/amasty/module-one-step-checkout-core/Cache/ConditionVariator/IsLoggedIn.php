<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Cache\ConditionVariator;

use Amasty\CheckoutCore\Api\CacheKeyPartProviderInterface;

/**
 * Add cache variation for logged customer and guest
 */
class IsLoggedIn implements CacheKeyPartProviderInterface
{
    /**
     * @var \Magento\Framework\App\Http\Context
     */
    private $httpContext;

    public function __construct(\Magento\Framework\App\Http\Context $httpContext)
    {
        $this->httpContext = $httpContext;
    }

    /**
     * @return string
     */
    public function getKeyPart()
    {
        if ($this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH)) {
            return 'logged';
        }

        return 'guest';
    }
}
