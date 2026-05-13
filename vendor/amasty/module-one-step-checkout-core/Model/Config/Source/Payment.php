<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Config\Source;

use Magento\Payment\Model\Config\Source\Allmethods;

class Payment extends Allmethods
{
    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        $options = parent::toOptionArray();

        array_unshift($options, ['value' => '', 'label' => ' ']);

        foreach ($options as $key => $option) {
            if (!isset($options[$key]['value'])) {
                $options[$key]['value'] = null;
            }
        }

        return $options;
    }
}
