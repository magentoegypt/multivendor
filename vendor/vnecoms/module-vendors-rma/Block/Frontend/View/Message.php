<?php

namespace Vnecoms\VendorsRMA\Block\Frontend\View;


class Message extends \Vnecoms\RMA\Block\Frontend\View\Message
{

    /**
     * check is show button reply
     * @return bool
     */
    public function isShowButtonReply(){
        if(
            $this->getRequestRma()->getState() == \Vnecoms\RMA\Model\Request::STATE_OPEN ||
            $this->getRequestRma()->getState() == \Vnecoms\VendorsRMA\Model\Request::STATE_AWAITING ||
            $this->getRequestRma()->getState() == \Vnecoms\VendorsRMA\Model\Request::STATE_BEING
        ) return true;
        return false;
    }

}