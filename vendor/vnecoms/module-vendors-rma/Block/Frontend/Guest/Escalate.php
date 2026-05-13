<?php

namespace Vnecoms\VendorsRMA\Block\Frontend\Guest;

class Escalate extends \Vnecoms\VendorsRMA\Block\Frontend\Escalate
{

    /**
     * get save Escalate URL
     */
    public function getSaveNoteUrl() {
        return $this->getUrl("vrma/guest/saveEscalate");
    }


}