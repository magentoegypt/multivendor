<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */
namespace Vnecoms\Quotation\Model\Source;

use Magento\Framework\Option\ArrayInterface;
use Vnecoms\Quotation\Model\Quote;

/**
 * Class Environment
 */
class Status implements ArrayInterface
{
    /**
     * Possible types
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => Quote::STATUS_CREATED,
                'label' => __('Starting'),
            ],
            [
                'value' => Quote::STATUS_CREATED_NOT_SENT,
                'label' => __('Created, not sent')
            ],
            [
                'value' => Quote::STATUS_PROCESSING,
                'label' => __('Processing')
            ],
            [
                'value' => Quote::STATUS_EXPIRED,
                'label' => __('Proposal Expired')
            ],
            [
                'value' => Quote::STATUS_HOLD,
                'label' => __('Proposal on Hold')
            ],
            [
                'value' => Quote::STATUS_CANCELLED,
                'label' => __('Proposal Cancelled')
            ],
            [
                'value' => Quote::STATUS_SENT,
                'label' => __('Proposal Sent')
            ],
            
            [
                'value' => Quote::STATUS_REJECTED,
                'label' => __('Proposal Rejected')
            ],
            [
                'value' => Quote::STATUS_ACCEPTED,
                'label' => __('Proposal Accepted')
            ],
            [
                'value' => Quote::STATUS_ORDERED,
                'label' => __('Ordered')
            ]
        ];
    }

    /**
     * get options as key value pair.
     *
     * @param array $options
     *
     * @return array
     */
    public function getOptions(array $options = [])
    {
        $_tmpOptions = $this->toOptionArray($options);
        $_options = [];
        foreach ($_tmpOptions as $option) {
            $_options[$option['value']] = $option['label'];
        }

        return $_options;
    }
}
