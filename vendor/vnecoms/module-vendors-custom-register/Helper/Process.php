<?php

namespace Vnecoms\VendorsCustomRegister\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class Process extends AbstractHelper
{
    /**
     * Directory List
     *
     * @var DirectoryList
     */
    protected $directoryList;
    /**
     * File interface
     *
     * @var File
     */
    protected $file;

    /**
     * @var \Magento\Eav\Model\Entity\Attribute
     */
    protected $eavAttribute;

    /**
     * Process constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Io\File $file
     * @param \Magento\Eav\Model\Entity\Attribute $eavAttribute
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Io\File $file,
        \Magento\Eav\Model\Entity\Attribute $eavAttribute
    ) {
        parent::__construct($context);
        $this->directoryList = $directoryList;
        $this->file = $file;
        $this->eavAttribute = $eavAttribute;
    }

    /**
     * @param $request
     * @return mixed
     */
    public function processBeforeRequest($request) {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

        $attributeCustomerRequest =$this->scopeConfig->getValue("vendors/create_account/default_require_attribute_values",
            $storeScope);

        if ($attributeCustomerRequest) {
            $attributeCustomerRequest = json_decode($attributeCustomerRequest, true);
            foreach ($attributeCustomerRequest as $item) {
                if (!$request->getParam($item['attribute'])) {
                    $attributeInfo = $this->eavAttribute ->loadByCode("customer", $item['attribute']);
                    switch ($attributeInfo->getData("frontend_input")) {
                        case 'image':
                        case 'file':
                            $this->uploadFile($item['attribute'], $item['value']);
                            break;
                        default:
                            $request->setParam($item['attribute'], $item['value']);
                            break;
                    }
                }
            }
        }

        return $request;
    }

    /**
     * Main service executor
     *
     * @param Product $product
     * @param string $imageUrl
     * @param array $imageType
     * @param bool $visible
     *
     * @return bool
     */
    protected function uploadFile($attributeCode, $imageUrl)
    {
        /** @var string $tmpDir */
        $tmpDir = $this->getMediaDirTmpDir();
        /** create folder if it is not exists */
        $this->file->checkAndCreateFolder($tmpDir);
        try {
            /** @var string $newFileName */
            $newFileName = $tmpDir . baseName($imageUrl);
            /** read file from URL and copy it to the new destination */
            $result = $this->file->read($imageUrl, $newFileName);
            if ($result) {
                $path_parts = pathinfo($newFileName);
                if (!isset($_FILES[$attributeCode])) {
                    $_FILES[$attributeCode] = [
                        "name" => $path_parts['basename'],
                        "tmp_name" => $newFileName,
                        "type" => $path_parts['extension']
                    ];
                }
            }
        } catch (\Exception $e) {
            //to do something
        }
        return $result;
    }


    /**
     * Media directory name for the temporary file storage
     * pub/media/tmp
     *
     * @return string
     */
    protected function getMediaDirTmpDir()
    {
        return $this->directoryList->getPath(DirectoryList::MEDIA) . DIRECTORY_SEPARATOR . 'tmp'.DIRECTORY_SEPARATOR.'customer'.DIRECTORY_SEPARATOR;
    }
}
