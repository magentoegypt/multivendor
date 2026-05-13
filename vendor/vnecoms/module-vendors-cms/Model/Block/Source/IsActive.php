<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model\Block\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class IsActive.
 */
class IsActive implements OptionSourceInterface
{
    /**
     * @var \Vnecoms\VendorsCms\Model\Block
     */
    protected $cmsBlock;

    /**
     * Constructor.
     *
     * @param \Vnecoms\VendorsCms\Model\Block $cmsBlock
     */
    public function __construct(\Vnecoms\VendorsCms\Model\Block $cmsBlock)
    {
        $this->cmsBlock = $cmsBlock;
    }

    /**
     * Get options.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $availableOptions = $this->cmsBlock->getAvailableStatuses();
        $options = [];
        foreach ($availableOptions as $key => $value) {
            $options[] = [
                'label' => $value,
                'value' => $key,
            ];
        }

        return $options;
    }
}
