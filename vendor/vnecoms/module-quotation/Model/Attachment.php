<?php

namespace Vnecoms\Quotation\Model;

class Attachment extends \Magento\Framework\Model\AbstractModel
{
    protected $content;
    protected $mimeType;
    protected $filename;
    protected $disposition;
    protected $encoding;

    /**
     * Attachment constructor.
     * @param $content
     * @param $mimeType
     * @param $fileName
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @param string $disposition
     * @param string $encoding
     */
    public function __construct(
        $content,
        $mimeType,
        $fileName,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection,
        array $data,
        $disposition = \Laminas\Mime\Mime::DISPOSITION_ATTACHMENT,
        $encoding = \Laminas\Mime\Mime::ENCODING_BASE64
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->content = $content;
        $this->mimeType = $mimeType;
        $this->filename = $fileName;
        $this->disposition = $disposition;
        $this->encoding = $encoding;
    }

    /**
     * @return mixed
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * @return mixed
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @return string
     */
    public function getDisposition()
    {
        return $this->disposition;
    }

    /**
     * @return string
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }
}
