<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 */

namespace Vnecoms\Quotation\Model;

class AttachmentContainer implements Api\AttachmentContainerInterface
{
    protected $attachments = [];

    /**
     * @return bool
     */
    public function hasAttachments()
    {
        return sizeof($this->attachments) >= 1;
    }

    /**
     * @param \Magento\Framework\DataObject $attachment
     */
    public function addAttachment(\Magento\Framework\DataObject $attachment)
    {
        $this->attachments[] = $attachment;
    }

    /**
     * @return array
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     */
    public function resetAttachments()
    {
        $this->attachments = [];
    }
}
