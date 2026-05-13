<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model\Page\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class IsActive.
 */
class IsActive implements OptionSourceInterface
{
    /**
     * @var \Vnecoms\VendorsCms\Model\Page
     */
    protected $cmsPage;

    /**
     * Constructor.
     *
     * @param \Vnecoms\VendorsCms\Model\Page $cmsPage
     */
    public function __construct(\Vnecoms\VendorsCms\Model\Page $cmsPage)
    {
        $this->cmsPage = $cmsPage;
    }

    /**
     * Get options.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $availableOptions = $this->cmsPage->getAvailableStatuses();
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
