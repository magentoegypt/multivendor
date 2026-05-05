<?php
namespace MagentoEgypt\VendorExtend\Plugin;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use DateTime;

class FormatDateHtml
{
    protected $timezone;

    public function __construct(TimezoneInterface $timezone)
    {
        $this->timezone = $timezone;
    }

    public function afterGetFormatDateHtml($subject, $result, $date)
    {
        $utcDateTime = new \DateTime($date);
        $dateObj = $this->timezone->date($utcDateTime);
        return $dateObj->format('F j, Y, g:i a');
    }
}
