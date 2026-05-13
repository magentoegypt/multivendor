<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Block\Catalog\Product\View\Type;

use Magento\Bundle\Block\Catalog\Product\View\Type\Bundle;

class BundleOverride extends Bundle
{
    /**
     * @param bool $stripSelection
     * @return array
     */
    public function getOptions($stripSelection = false)
    {
        $this->options = null;

        return parent::getOptions($stripSelection);
    }
}
