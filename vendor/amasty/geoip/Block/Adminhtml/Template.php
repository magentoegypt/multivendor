<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package GeoIP Data for Magento 2 (System)
 */

namespace Amasty\Geoip\Block\Adminhtml;

class Template extends \Magento\Backend\Block\Template
{
    /**
     * @var \Amasty\Geoip\Helper\Data
     */
    public $geoipHelper;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Amasty\Geoip\Helper\Data $geoipHelper
    ) {
        parent::__construct($context);

        $this->geoipHelper = $geoipHelper;
    }
}
