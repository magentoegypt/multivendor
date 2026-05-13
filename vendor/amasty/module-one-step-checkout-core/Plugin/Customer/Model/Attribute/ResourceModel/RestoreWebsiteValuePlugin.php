<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Plugin\Customer\Model\Attribute\ResourceModel;

use Amasty\CheckoutCore\Plugin\Customer\Model\Attribute\SetWebsitePlugin;
use Magento\Customer\Model\ResourceModel\Attribute as Subject;
use Magento\Framework\Model\AbstractModel;

class RestoreWebsiteValuePlugin
{
    /**
     * @see \Magento\Customer\Model\Attribute::getWebsite is unreliable as it may return the storefront website
     * even when attribute is loaded in default scope.
     *
     * @see SetWebsitePlugin makes attribute hold real website the attribute is loaded in.
     * This plugin preserves this value after calling @see \Magento\Customer\Model\Attribute::load.
     *
     * @param Subject $subject
     * @param callable $proceed
     * @param AbstractModel $object
     * @param mixed $value
     * @param string|null $field
     * @see Subject::load
     * @return Subject
     */
    public function aroundLoad(
        Subject $subject,
        callable $proceed,
        AbstractModel $object,
        $value,
        $field = null
    ) {
        $website = $object->getData(SetWebsitePlugin::KEY_WEBSITE);
        $proceed($object, $value, $field);

        if ($website) {
            $object->setData(SetWebsitePlugin::KEY_WEBSITE, $website);
        }

        return $subject;
    }
}
