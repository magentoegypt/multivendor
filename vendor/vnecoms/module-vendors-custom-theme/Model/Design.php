<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCustomTheme\Model;

class Design extends \Magento\Framework\DataObject
{
    /**
     * Design package instance
     *
     * @var \Magento\Framework\View\DesignInterface
     */
    protected $_design = null;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;

    /**
     * 
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Framework\View\DesignInterface $design
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\View\DesignInterface $design,
        array $data = []
    ) {
        $this->_localeDate = $localeDate;
        $this->_design = $design;
        parent::__construct($data);
    }

    /**
     * Apply custom design
     *
     * @param string $design
     * @return $this
     */
    public function applyCustomDesign(\Vnecoms\VendorsCustomTheme\Model\Theme $theme)
    {
        if($theme->getThemeId()){
            $this->_design->setDesignTheme($theme->getBaseThemeId());
        }
        return $this;
    }

}
