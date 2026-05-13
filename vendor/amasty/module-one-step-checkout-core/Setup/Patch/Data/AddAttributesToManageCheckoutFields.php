<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * @deprecated
 * @see \Amasty\CheckoutCore\Setup\RecurringData
 */
class AddAttributesToManageCheckoutFields implements DataPatchInterface
{
    public function apply(): AddAttributesToManageCheckoutFields
    {
        return $this;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
