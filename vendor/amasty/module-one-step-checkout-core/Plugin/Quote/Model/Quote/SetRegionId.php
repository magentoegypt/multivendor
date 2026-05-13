<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Plugin\Quote\Model\Quote;

use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\Quote;

class SetRegionId
{
    /**
     * @var AddressInterface
     */
    private $address;

    /**
     * @param Quote $subject
     * @param AddressInterface|null $address
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSetShippingAddress(Quote $subject, AddressInterface $address = null): void
    {
        $this->address = $address;
    }

    /**
     * @param Quote $subject
     * @param Quote $result
     * @return Quote
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSetShippingAddress(Quote $subject, Quote $result): Quote
    {
        if ($this->address
            && !$this->address->getRegionId()
            && $this->address->getId() == $subject->getShippingAddress()->getId()
        ) {
            $subject->getShippingAddress()->setRegionId(null);
        }

        return $result;
    }
}
