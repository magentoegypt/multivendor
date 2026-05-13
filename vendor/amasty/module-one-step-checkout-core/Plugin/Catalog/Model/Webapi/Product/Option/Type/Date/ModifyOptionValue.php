<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Plugin\Catalog\Model\Webapi\Product\Option\Type\Date;

use Amasty\CheckoutCore\Model\Config;
use Magento\Catalog\Model\Webapi\Product\Option\Type\Date;

/**
 * Modify Product Custom Option Value For Edit On Checkout
 */
class ModifyOptionValue
{
    /**
     * @var Config
     */
    private $configProvider;

    public function __construct(Config $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * @param Date $subject
     * @param array $values
     * @return array
     */
    public function beforeValidateUserValue(Date $subject, array $values): array
    {
        $optionId = $subject->getOption()->getId();
        if (!empty($values[$optionId])
            && is_array($values[$optionId])
            && $this->configProvider->isEnabled()
            && $this->configProvider->isCheckoutItemsEditable()
        ) {
            $values[$optionId] = $this->modifyValue($values[$optionId]);
        }

        return [$values];
    }

    /**
     * @param array $value
     * @return string
     */
    private function modifyValue(array $value): string
    {
        $stringDate = isset($value["year"]) ? $value["year"] . '-' : '1970' . '-';
        $stringDate .= isset($value["month"]) ? $value["month"] . '-' : '01' . '-';
        $stringDate .= isset($value["day"]) ? $value["day"] . ' ' : '01' . ' ';
        $stringDate .= isset($value["hour"]) ? $this->handleTimeUnit($value["hour"]) : '00' . ':';
        $stringDate .= isset($value["minute"]) ? $this->handleTimeUnit($value["minute"]) : '00' . ':';
        $stringDate .= '00';

        return $stringDate;
    }

    private function handleTimeUnit(string $timeUnit): string
    {
        $timeUnitResult = '';
        if (strlen($timeUnit) < 2) {
            $timeUnitResult .= '0' . $timeUnit . ':';
        } else {
            $timeUnitResult .= $timeUnit . ':';
        }

        return $timeUnitResult;
    }
}
