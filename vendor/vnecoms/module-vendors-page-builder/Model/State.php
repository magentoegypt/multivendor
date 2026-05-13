<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\VendorsPageBuilder\Model;

class State
{
    /**
     * @var \Vnecoms\VendorsPageBuilder\Model\Config
     */
    private $config;

    /**
     * State constructor.
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Returns information if use page builder based on system configuration and xml configuration
     * @param $isPageBuilderUsed
     * @return bool
     */
    public function isPageBuilderInUse($isPageBuilderUsed) : bool
    {
        return $isPageBuilderUsed || !$this->config->isEnabled();
    }
}
