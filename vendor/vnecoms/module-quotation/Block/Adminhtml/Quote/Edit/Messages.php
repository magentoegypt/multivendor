<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */
namespace Vnecoms\Quotation\Block\Adminhtml\Quote\Edit;

/**
 * Quote create errors block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Messages extends \Magento\Framework\View\Element\Messages
{
    /**
     * Preparing global layout
     *
     * @return void
     */
    protected function _prepareLayout()
    {
        $this->addMessages($this->messageManager->getMessages(true));
        parent::_prepareLayout();
    }
}
