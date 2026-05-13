<?php

namespace Vnecoms\RMA\Model;

use Magento\Framework\Model\AbstractModel;
use Vnecoms\RMA\Api\Data\MessageInterface;

class Message extends AbstractModel implements MessageInterface
{


    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Eav\Model\Config $config
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Vnecoms\RMA\Model\ResourceModel\Message $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Eav\Model\Config $config,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Vnecoms\RMA\Model\ResourceModel\Message $resource,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_storeManager = $storeManager;
        $this->_scopeConfig = $scopeConfig;
        $this->_config = $config;
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('Vnecoms\RMA\Model\ResourceModel\Message');
    }

    /**
     * Retrieve attachtment URL
     *
     * @return string
     */
    public function getAttachmentUrls($store = true)
    {
        $url = [];
        $files = explode(",", $this->getAttachment());
        if (count($files) && $this->getAttachment()) {
            foreach ($files as $file) {
                if (is_string($file)) {
                    if ($store) {
                        $urlStore =  $this->_storeManager->getStore()->getBaseUrl(
                            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                        ) . 'rma/request/' . $file;
                    } else {
                        $urlStore =  $this->_storeManager->getStore()->getBaseUrl(
                            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                        ) . 'rma/index/' . $file;
                    }

                    $url[$file] = $urlStore;
                }
            }
        }
        // var_dump($url);exit;
        return $url;
    }

    /**
     * Retrieve attachtment URL
     *
     * @return string
     */
    public function getAttachmentToObject($store = true)
    {
        $url = [];
        $files = explode(",", $this->getAttachment());
        if (count($files) && $this->getAttachment()) {
            foreach ($files as $file) {
                if (is_string($file)) {
                    if ($store) {
                        $urlStore =  $this->_storeManager->getStore()->getBaseUrl(
                            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                        ) . 'rma/request/' . $file;
                    } else {
                        $urlStore =  $this->_storeManager->getStore()->getBaseUrl(
                            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                        ) . 'rma/request/' . $file;
                    }
                    $result = new \Magento\Framework\DataObject(['name'=>$file , 'url' => $urlStore]);
                    $url[] = $result->getData();
                }
            }
        }
        return $url;
    }


    /**#@-*/

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getMessageId()
    {
        return $this->getData(self::MESSAGE_ID);
    }


    /**
     * Get Request Id
     *
     * @return string|null
     */
    public function getRequestId()
    {
        return $this->getData(self::REQUEST_ID);
    }

    /**
     * Get Message
     *
     * @return string|null
     */
    public function getMessage()
    {
        return $this->getData(self::MESSAGE);
    }

    /**
     * Get Attachment
     *
     * @return string|null
     */
    public function getAttachments()
    {
        return $this->getAttachmentToObject();
        // return $this->getData(self::MESSAGE);
    }


    /**
     * Get Type
     *
     * @return string|null
     */
    public function getType()
    {
        return $this->getData(self::TYPE);
    }


    /**
     * Get From
     *
     * @return string|null
     */
    public function getFrom()
    {
        return $this->getData(self::FROM);
    }

    /**
     * Get To
     *
     * @return string|null
     */
    public function getTo()
    {
        return $this->getData(self::TO);
    }


    /**
     * Get Created At
     *
     * @return string|null
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * Get Updated At At
     *
     * @return string|null
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }


    /**
     * Set ID
     *
     * @param int $id
     * @return \Vnecoms\RMA\Api\Data\MessageInterface
     */
    public function setMessageId($id)
    {
        return $this->setData(self::MESSAGE_ID, $id);
    }


    /**
     * Set Request Id
     *
     * @param int $ticketId
     * @return \Vnecoms\RMA\Api\Data\MessageInterface
     */
    public function setRequestId($request)
    {
        return $this->setData(self::REQUEST_ID, $request);
    }

    /**
     * Set MEssage
     *
     * @param string $content
     * @return \Vnecoms\RMA\Api\Data\MessageInterface
     */
    public function setMessage($content)
    {
        return $this->setData(self::MESSAGE, $content);
    }

    /**
     * Set Attachment
     *
     * @param string $attachment
     * @return \Vnecoms\RMA\Api\Data\MessageInterface
     */
    public function setAttachment($attachment)
    {
    }

    /**
     * Set Type
     *
     * @param string $type
     * @return \Vnecoms\RMA\Api\Data\MessageInterface
     */
    public function setType($type)
    {
        return $this->setData(self::TYPE, $type);
    }


    /**
     * Set From
     *
     * @param string $from
     * @return \Vnecoms\RMA\Api\Data\MessageInterface
     */
    public function setFrom($from)
    {
        return $this->setData(self::FROM, $from);
    }

    /**
     * Set To
     *
     * @param string $to
     * @return \Vnecoms\RMA\Api\Data\MessageInterface
     */
    public function setTo($to)
    {
        return $this->setData(self::TO, $to);
    }


    /**
     * Set Created At
     *
     * @param string $time
     * @return \Vnecoms\RMA\Api\Data\MessageInterface
     */
    public function setCreatedAt($time)
    {
        return $this->setData(self::CREATED_AT, $time);
    }

    /**
     * Set Updated At
     *
     * @param string $time
     * @return \Vnecoms\RMA\Api\Data\MessageInterface
     */
    public function setUpdatedAt($time)
    {
        return $this->setData(self::UPDATED_AT, $time);
    }
}
