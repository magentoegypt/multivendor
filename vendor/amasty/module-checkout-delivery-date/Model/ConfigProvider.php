<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Delivery Date for Magento 2 (System)
 */

namespace Amasty\CheckoutDeliveryDate\Model;

use Amasty\Base\Model\ConfigProviderAbstract;
use Magento\Store\Model\ScopeInterface;

class ConfigProvider extends ConfigProviderAbstract
{
    /**
     * Path Prefix For Config
     */
    public const PATH_PREFIX = 'amasty_checkout/';

    public const DELIVERY_DATE_BLOCK = 'delivery_date/';

    public const DD_ENABLED = 'enabled';
    public const DD_REQUIRED = 'date_required';
    public const AVAILABLE_DAYS = 'available_days';
    public const AVAILABLE_HOURS = 'available_hours';
    public const COMMENT_ENABLED = 'delivery_comment_enable';
    public const COMMENT_DEFAULT = 'delivery_comment_default';

    public const XPATH_FIRST_DAY = 'general/locale/firstday';

    /**
     * xpath prefix of module (section)
     *
     * @var string
     */
    protected $pathPrefix = self::PATH_PREFIX;

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isDeliveryDateEnabled(int $storeId = null): bool
    {
        return $this->isSetFlag(self::DELIVERY_DATE_BLOCK . self::DD_ENABLED, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isDeliveryDateRequired(int $storeId = null): bool
    {
        return $this->isSetFlag(self::DELIVERY_DATE_BLOCK . self::DD_REQUIRED, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return array
     */
    public function getDeliveryDays(int $storeId = null): array
    {
        $days = $this->getValue(self::DELIVERY_DATE_BLOCK . self::AVAILABLE_DAYS, $storeId);
        if (!$days) {
            return [];
        }

        $days = explode(',', $days);
        foreach ($days as &$day) {
            $day = (int)$day;
        }

        return $days;
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isCommentEnabled(int $storeId = null): bool
    {
        return $this->isSetFlag(self::DELIVERY_DATE_BLOCK . self::COMMENT_ENABLED, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getDefaultComment(int $storeId = null): string
    {
        return (string)$this->getValue(self::DELIVERY_DATE_BLOCK . self::COMMENT_DEFAULT, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return int
     */
    public function getFirstDay(int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(self::XPATH_FIRST_DAY, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return array
     */
    public function getDeliveryHours(int $storeId = null): array
    {
        $hoursSetting = (string)$this->getValue(self::DELIVERY_DATE_BLOCK . self::AVAILABLE_HOURS, $storeId);
        $intervals = preg_split('#\s*,\s*#', $hoursSetting, -1, PREG_SPLIT_NO_EMPTY);

        $hours = $this->getHours($intervals);
        if (!$hours) {
            $hours = range(0, 23);
        } else {
            $hours = array_unique($hours);
            asort($hours);
        }

        $options = [[
            'value' => '-1',
            'label' => ' ',
        ]];

        foreach ($hours as $hour) {
            $options [] = [
                'value' => $hour,
                'label' => $hour . ':00 - ' . (($hour) + 1) . ':00',
            ];
        }

        return $options;
    }

    /**
     * @param array $intervals
     * @return array
     */
    private function getHours(array $intervals): array
    {
        $hours = [];
        foreach ($intervals as $interval) {
            if (preg_match('#(?P<lower>\d+)(\s*-\s*(?P<upper>\d+))?#', $interval, $matches)) {
                $lower = (int)$matches['lower'];
                if ($lower > 23) {
                    continue;
                }

                if (isset($matches['upper'])) {
                    $upper = (int)$matches['upper'];
                    if ($upper > 24) {
                        continue;
                    }

                    $upper--;

                    if ($lower > $upper) {
                        continue;
                    }
                } else {
                    $upper = $lower;
                }

                $range = range($lower, $upper);
                $hours = $this->mergeHours($hours, $range);
            }
        }

        return $hours;
    }

    /**
     * @param array $hours
     * @param array $range
     * @return array
     */
    private function mergeHours(array $hours, array $range): array
    {
        return array_merge($hours, $range);
    }
}
