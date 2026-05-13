<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Api;

interface MergeJsInterface
{
    /**
     * @param string[] $fileNames
     * @return boolean
     */
    public function createBundle(array $fileNames);
}
