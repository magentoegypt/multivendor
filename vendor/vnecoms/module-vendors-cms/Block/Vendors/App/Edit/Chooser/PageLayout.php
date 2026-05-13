<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Block\Vendors\App\Edit\Chooser;

/**
 * Widget Instance layouts chooser.
 */
class PageLayout extends \Magento\Framework\View\Element\Html\Select
{
    /**
     * PageLayout constructor.
     * @param \Magento\Framework\View\Element\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Add necessary options.
     *
     * @return \Magento\Framework\View\Element\AbstractBlock
     */
    protected function _beforeToHtml()
    {
        if (!$this->getOptions()) {
            $this->addOption('', __('-- Please Select --'));
            $this->addOption('1column', '1 Column', []);
            $this->addOption('2columns-left', '2 Columns Left', []);
            $this->addOption('2columns-right', '2 Columns Right', []);
            $this->addOption('3columns', '3 Columns', []);
        }

        return parent::_beforeToHtml();
    }
}
