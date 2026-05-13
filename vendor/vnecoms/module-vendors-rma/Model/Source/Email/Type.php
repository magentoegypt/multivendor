<?php

namespace Vnecoms\VendorsRMA\Model\Source\Email;

class Type extends \Magento\Framework\DataObject
{
    const TYPE_NEW	= 'NEW MESSAGE';
    const TYPE_REPLY_CUSTOMER	= 'CUSTOMER REPLY';
    const TYPE_REPLY_DEPARMENT	= 'DEPARMENT REPLY';
    const TYPE_REPLY_VENDOR	= 'VENDOR REPLY';

    const IS_SHOW_ENABLE=1;
    const IS_SHOW_DISABLE=0;
    const REPLY_BY_DEPARTMENT	= 'reply_by_department';
    const REPLY_BY_CUSTOMER	= 'reply_by_customer';
    const NOTIFY_STATUS_CUSTOMER	= 'notify_status_customer';
    const NOTIFY_STATUS_DEPARTMENT	= 'notify_status_department';
    const NOTIFY_REFUND_PRICE_CUSTOMER	= 'notify_refund_price_customer';
    const NOTIFY_REFUND_PRICE_VENDOR	= 'notify_refund_price_vendor';
}


