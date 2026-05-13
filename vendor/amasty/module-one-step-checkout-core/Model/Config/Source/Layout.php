<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Layout implements OptionSourceInterface
{
    public const ONE_COLUMN = '1column';
    public const TWO_COLUMNS = '2columns';
    public const THREE_COLUMNS = '3columns';

    /**
     * @return array[]
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::TWO_COLUMNS, 'label' => __('2 Columns')],
            ['value' => self::THREE_COLUMNS, 'label' => __('3 Columns')],
        ];
    }
}
