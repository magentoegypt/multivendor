<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\Quotation\Ui\Component\Listing\Column\Status;

use Magento\Framework\Data\OptionSourceInterface;
use Vnecoms\Quotation\Model\Source\Status;

/**
 * Class Options
 */
class Options implements OptionSourceInterface
{
    /**
     * @var array
     */
    protected $options;

    protected $status;

    public function __construct(Status $status)
    {
        $this->status = $status;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->options === null) {
            $this->options = $this->status->toOptionArray();
        }
        return $this->options;
    }
}
