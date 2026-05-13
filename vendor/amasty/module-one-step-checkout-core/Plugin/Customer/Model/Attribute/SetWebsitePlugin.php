<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Plugin\Customer\Model\Attribute;

use Magento\Customer\Model\Attribute;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;

class SetWebsitePlugin
{
    public const KEY_WEBSITE = 'am_website';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
    }

    /**
     * @param Attribute $subject
     * @param Attribute $attribute
     * @param Website|int $website
     * @return Attribute
     * @throws LocalizedException
     * @see Attribute::setWebsite
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSetWebsite(Attribute $subject, Attribute $attribute, $website): Attribute
    {
        $attribute->setData(self::KEY_WEBSITE, $this->storeManager->getWebsite($website));
        return $attribute;
    }
}
