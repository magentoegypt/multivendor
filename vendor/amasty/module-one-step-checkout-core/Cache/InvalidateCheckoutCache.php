<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Cache;

use Magento\Framework\App\Cache\TypeListInterface;

class InvalidateCheckoutCache
{
    /**
     * @var TypeListInterface
     */
    private $cacheTypeList;

    public function __construct(TypeListInterface $cacheTypeList)
    {
        $this->cacheTypeList = $cacheTypeList;
    }

    public function execute(): void
    {
        $this->cacheTypeList->invalidate(Type::TYPE_IDENTIFIER);
    }
}
