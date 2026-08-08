<?php
/**
 * Options for the seller-declared dispatch time.
 *
 * Banded rather than free text so the values are comparable between stores and
 * translatable. Values are stable strings, not labels, so re-wording a label
 * later does not orphan existing seller choices.
 */
declare(strict_types=1);

namespace MagentoEgypt\HomeSections\Model\Vendor\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class DispatchTime extends AbstractSource
{
    public const SAME_DAY = 'same_day';
    public const NEXT_DAY = 'next_day';
    public const DAYS_2_3 = 'days_2_3';
    public const DAYS_3_5 = 'days_3_5';
    public const DAYS_5_7 = 'days_5_7';

    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ['value' => '',               'label' => __('-- Not specified --')],
                ['value' => self::SAME_DAY,   'label' => __('Ships same day')],
                ['value' => self::NEXT_DAY,   'label' => __('Ships next business day')],
                ['value' => self::DAYS_2_3,   'label' => __('Ships in 2-3 business days')],
                ['value' => self::DAYS_3_5,   'label' => __('Ships in 3-5 business days')],
                ['value' => self::DAYS_5_7,   'label' => __('Ships in 5-7 business days')],
            ];
        }
        return $this->_options;
    }
}
