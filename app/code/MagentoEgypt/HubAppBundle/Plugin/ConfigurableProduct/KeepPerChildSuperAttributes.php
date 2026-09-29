<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppBundle\Plugin\ConfigurableProduct;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\DataObject;
use MagentoEgypt\HubAppBundle\Model\Cart\SuperAttributeKeeper;

/**
 * GraphQL area only: after a configurable bundle child is prepared for the
 * cart, give the shared buy request back its full per-child super_attribute map
 * (see SuperAttributeKeeper). A no-op for any buy request hmAddBundleToCart did
 * not register.
 */
class KeepPerChildSuperAttributes
{
    public function __construct(private readonly SuperAttributeKeeper $keeper)
    {
    }

    /**
     * @param Configurable $subject
     * @param mixed $result
     * @param DataObject $buyRequest
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterPrepareForCart(Configurable $subject, $result, DataObject $buyRequest)
    {
        $this->keeper->restore($buyRequest);

        return $result;
    }
}
