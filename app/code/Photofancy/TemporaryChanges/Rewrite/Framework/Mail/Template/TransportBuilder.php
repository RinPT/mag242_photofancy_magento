<?php

namespace Photofancy\TemporaryChanges\Rewrite\Framework\Mail\Template;

use Zend\Mime\Mime;

class TransportBuilder extends \Magento\Framework\Mail\Template\TransportBuilder
{
    /**
     * Add an attachment to the message.
     *
     * @param string $attachment
     * @return $this
     */
    public function addAttachment($attachment)
    {
        $body = (isset($attachment['body'])) ? $attachment['body'] : '';
        if (strlen($body)) {
            $filename = (isset($attachment['filename'])) ? $attachment['filename'] : null;
            $fileType = Mime::TYPE_OCTETSTREAM;
            $this->message->setBodyAttachment($body, $filename, $fileType);
        }
        return $this;
    }
    /**
     * After all parts are set, add them to message body.
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function prepareMessage()
    {
        parent::prepareMessage();
        $this->message->setPartsToBody();
        return $this;
    }
}
