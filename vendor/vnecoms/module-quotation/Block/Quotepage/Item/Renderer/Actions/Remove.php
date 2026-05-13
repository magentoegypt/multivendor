<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 */
namespace Vnecoms\Quotation\Block\Quotepage\Item\Renderer\Actions;

use Magento\Framework\View\Element\Template;

class Remove extends Generic
{

    public function __construct(
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getDeletePostJson()
    {
        return json_encode([
            'action' => $this->getUrl('quotation/quote/delete'),
            'data' => [
                'id' => $this->getItem()->getId()
            ]
        ]);
    }
}
