<?php

namespace Vnecoms\Quotation\Model;

/**
 * Class MailTransportBuilder.
 *
 * @author Vnecoms team <vnecoms.com>
 */
class MailTransportBuilder extends \Magento\Framework\Mail\Template\TransportBuilder
{
    /**
     * @param \Magento\Framework\DataObject $attachment
     */
    public function addAttachment(\Vnecoms\Quotation\Model\Message\Attachment $attachment)
    {
        $this->message->createAttachment(
            $attachment->getFileContent(),
            $attachment->getMimeType(),
            \Laminas\Mime\Mime::DISPOSITION_ATTACHMENT,
            \Laminas\Mime\Mime::ENCODING_BASE64,
            $this->encodedFileName($attachment->getName())
        );
    }

    /**
     * Encode file name
     *
     * @param string $subject
     * @return string
     */
    protected function encodedFileName($subject)
    {
        return sprintf('=?utf-8?B?%s?=', base64_encode($subject));
    }
}
