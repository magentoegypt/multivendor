<?php


namespace Vnecoms\VendorsRMA\Model\Source\Message;


class Type extends \Vnecoms\RMA\Model\Source\Message\Type
{
    const TYPE_REPLY_VENDOR	= 'VENDOR REPLY';
    const CHANGE_BY_VENDOR	= 'vendor';
    const CHANGE_BY_ADMIN	= 'admin';
}
