<?php

namespace Vnecoms\VendorsRMA\Model\Request;

use Magento\Framework\Model\AbstractModel;

class Escalate extends AbstractModel
{
    /**
     * File uploader
     *
     * @var \Vnecoms\HelpDesk\Model\FileUploader
     */
    private $fileUploader;
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
        \Vnecoms\VendorsRMA\Model\ResourceModel\Escalate $resource,
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
        $this->_init('Vnecoms\VendorsRMA\Model\ResourceModel\Escalate');
    }

    /**
     * Retrieve attachtment URL
     *
     * @return string
     */
    public function getAttachmentUrls($store = true)
    {
        $url = array();
        $files = explode(",",$this->getAttachment());
        if (count($files) && $this->getAttachment()) {
            foreach ($files as $file){
                if (is_string($file)) {
                    if($store){
                        $urlStore =  $this->_storeManager->getStore()->getBaseUrl(
                                \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                            ) . 'rma/request/' . $file;
                    }else{
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
     * Get Attachment uploader
     *
     * @return \Vnecoms\HelpDesk\Model\FileUploader
     *
     * @deprecated
     */
    private function getFileUploader()
    {
        if ($this->fileUploader === null) {
            $this->fileUploader = \Magento\Framework\App\ObjectManager::getInstance()->get(
                'Vnecoms\RMA\RequestFileUploader'
            );
        }
        return $this->fileUploader;
    }


    /**
     * Update Escalate data attachment
     * @param $data
     */

    public function updateEscalateAttachment($attachments) {
        if(!$attachments) return $this;
        $curentAttachment= trim($this->getAttachment(),",");
        $attachmens = explode(",",$attachments);
        $newAttachment = [];
        foreach ($attachmens as $file){
            $newFile = $this->getFileUploader()->moveFileFromTmp($file);
            if($newFile) $newAttachment[] = $newFile;
        }
        if ($newAttachment) {
            $newAttachment = implode(",",$newAttachment);
            $newAttachment = trim($newAttachment,",").",".$curentAttachment;
            $this->setAttachment($newAttachment);
        }
    }


}
