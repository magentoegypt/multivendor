<?php
/**
 * Copyright © 2017 Vnecoms, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\Quotation\Block\Email;

class Items extends \Vnecoms\Quotation\Block\Quote\Items
{
    public function getQuote()
    {
        $quoteId = $this->getData('quote_id');
        return $this->quoteFactory->create()->load($quoteId);
    }
}
