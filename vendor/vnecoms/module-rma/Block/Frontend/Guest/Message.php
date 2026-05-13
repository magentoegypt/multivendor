<?php

namespace Vnecoms\RMA\Block\Frontend\Guest;

class Message extends \Vnecoms\RMA\Block\Frontend\View\Message
{
    /**
     * @param object $order
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->getUrl('vrma/guest/reply');
    }
}
