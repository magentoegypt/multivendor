<?php

namespace Vnecoms\Quotation\Utils;

class OptionsBuilder {

    public function __construct()
    {

    }

    public function build($item)
    {
        return array_merge(
            $this->getBundleOptions($item),
            $this->getConfigurationOptions($item)
        );
    }

    public function getBundleOptions($item)
    {

    }

    public function getConfigurationOptions($item)
    {

    }
}