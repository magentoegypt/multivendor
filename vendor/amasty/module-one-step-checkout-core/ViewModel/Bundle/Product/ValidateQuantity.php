<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\ViewModel\Bundle\Product;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Catalog\Block\Product\View as ProductView;

/**
 * Temporary solution for backward compatibility
 * Since 2.4.4 version new view model was appeared
 *
 * @see \Magento\Bundle\ViewModel\ValidateQuantity
 */
class ValidateQuantity implements ArgumentInterface
{
    /**
     * @var Json
     */
    private $serializer;

    public function __construct(
        Json $serializer
    ) {
        $this->serializer = $serializer;
    }

    /**
     * Retrieve validators for quantity input to prevent insertion negative values
     *
     * @return string
     */
    public function getQuantityValidators(): string
    {
        $validators = ['required-number' => true];

        return $this->serializer->serialize($validators);
    }
}
