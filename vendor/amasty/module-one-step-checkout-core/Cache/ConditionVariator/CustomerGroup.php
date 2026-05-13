<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Cache\ConditionVariator;

use Amasty\CheckoutCore\Api\CacheKeyPartProviderInterface;

/**
 * Add cache variation for each customer group
 */
class CustomerGroup implements CacheKeyPartProviderInterface
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    public function __construct(\Magento\Customer\Model\Session $customerSession)
    {
        $this->customerSession = $customerSession;
    }

    /**
     * @return string
     */
    public function getKeyPart()
    {
        return 'cusGroup=' . $this->customerSession->getCustomerGroupId();
    }
}
