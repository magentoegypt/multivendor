<?php

namespace Vnecoms\RMA\Model;

use Magento\Framework\Model\AbstractModel;
use Vnecoms\RMA\Api\Data\ReponseInterface;

class Reponse extends AbstractModel implements ReponseInterface
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('Vnecoms\RMA\Model\ResourceModel\Reponse');
    }


    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->getData(self::TYPE_ID);
    }


    /**
     * Get title
     *
     * @return string|null
     */
    public function getTitle()
    {
        return $this->getData(self::TITLE);
    }

    /**
     * Get content template
     *
     * @return string|null
     */
    public function getContent()
    {
        return $this->getData(self::CONTENT);
    }

    /**
     * Get status
     *
     * @return string|null
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }


    /**
     * Set ID
     *
     * @param int $id
     * @return \Vnecoms\RMA\Api\Data\ReponseInterface
     */
    public function setId($id)
    {
        return $this->setData(self::TYPE_ID, $id);
    }


    /**
     * Set title
     *
     * @param string $title
     * @return \Vnecoms\RMA\Api\Data\ReponseInterface
     */
    public function setTitle($title)
    {
        return $this->setData(self::TITLE, $title);
    }

    /**
     * Set Content
     *
     * @param string $content
     * @return \Vnecoms\RMA\Api\Data\ReponseInterface
     */
    public function setContent($content)
    {
        return $this->setData(self::CONTENT, $content);
    }

    /**
     * Set status
     *
     * @param string $status
     * @return  \Vnecoms\RMA\Api\Data\ReponseInterface
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }
}
