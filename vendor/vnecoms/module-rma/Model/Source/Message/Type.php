<?php


namespace Vnecoms\RMA\Model\Source\Message;

class Type extends \Magento\Framework\DataObject
{

    const TYPE_NEW  = 'NEW MESSAGE';
    const TYPE_REPLY_CUSTOMER   = 'CUSTOMER REPLY';
    const TYPE_REPLY_DEPARMENT  = 'DEPARMENT REPLY';
    const CHANGE_BY_CUSTOMER    = 'customer';
    const CHANGE_BY_DEPARTMENT  = 'department';

    const IS_SHOW_ENABLE=1;
    const IS_SHOW_DISABLE=0;
}
