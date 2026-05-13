<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Model\App\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class IsActive.
 */
class Container implements OptionSourceInterface
{
    /**
     * @var \Magento\Framework\View\Layout\ProcessorFactory
     */
    protected $_layoutProcessorFactory;

    /**
     * @var \Magento\Theme\Model\ResourceModel\Theme\CollectionFactory
     */
    protected $_themesFactory;

    public function __construct(
        \Magento\Framework\View\Layout\ProcessorFactory $layoutProcessorFactory,
        \Magento\Theme\Model\ResourceModel\Theme\CollectionFactory $themesFactory
    ) {
        $this->_layoutProcessorFactory = $layoutProcessorFactory;
        $this->_themesFactory = $themesFactory;
    }

    public function toOptionArray()
    {
    }
}
