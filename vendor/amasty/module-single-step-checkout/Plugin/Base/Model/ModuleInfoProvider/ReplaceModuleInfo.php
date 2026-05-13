<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout for Magento 2
 */

namespace Amasty\Checkout\Plugin\Base\Model\ModuleInfoProvider;

use Amasty\Base\Model\ModuleInfoProvider;

class ReplaceModuleInfo
{
    /**
     * @param ModuleInfoProvider $subject
     * @param string $moduleCode
     * @return array|string[]
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeGetModuleInfo(ModuleInfoProvider $subject, string $moduleCode)
    {
        if ($moduleCode === 'Amasty_CheckoutCore') {
            $moduleCode = 'Amasty_Checkout';
        }

        return [$moduleCode];
    }
}
