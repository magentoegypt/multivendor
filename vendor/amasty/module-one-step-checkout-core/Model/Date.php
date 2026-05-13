<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model;

use Magento\Framework\Stdlib\DateTime;

class Date
{
    public const DAY = 86400;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var DateTime\DateTime
     */
    private $date;

    /**
     * @var DateTime\Timezone
     */
    private $timezone;

    public function __construct(
        Config $config,
        DateTime $dateTime,
        DateTime\DateTime $date,
        DateTime\Timezone $timezone
    ) {
        $this->dateTime = $dateTime;
        $this->date = $date;
        $this->timezone = $timezone;
        $this->config = $config;
    }

    /**
     * Return date with $days offset
     *
     * @param int $days
     *
     * @return string
     */
    public function getDateWithOffsetByDays($days)
    {
        $offset = $days * self::DAY;

        return $this->dateTime->formatDate($this->date->gmtTimestamp() + $offset, false);
    }

    /**
     * @param string $date
     *
     * @param string|int $store
     *
     * @param int $dateType
     *
     * @param int $timeType
     *
     * @return string
     */
    public function convertDate(
        $date,
        $store = null,
        $dateType = \IntlDateFormatter::SHORT,
        $timeType = \IntlDateFormatter::NONE
    ) {
        $storeLocale = $store ? $this->config->getStoreLocale($store) : null;

        return $this->timezone->formatDateTime(
            $date,
            $dateType,
            $timeType,
            $storeLocale
        );
    }

    /**
     * @param  string $format
     * @param  int|string $input date in GMT timezone
     *
     * @return string
     */
    public function date($format = null, $input = null)
    {
        return $this->date->date($format, $input);
    }
}
