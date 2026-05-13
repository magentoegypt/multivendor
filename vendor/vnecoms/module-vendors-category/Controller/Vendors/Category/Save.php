<?php

namespace Vnecoms\VendorsCategory\Controller\Vendors\Category;

/**
 * Class Save.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Save extends \Vnecoms\VendorsCategory\Controller\Vendors\Category
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_Vendors::category_action_save';
    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Framework\View\LayoutFactory
     */
    protected $layoutFactory;

    public function __construct(
        \Vnecoms\Vendors\App\Action\Context $context,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\View\LayoutFactory $layoutFactory
    ) {
        parent::__construct($context);
        $this->resultRawFactory = $resultRawFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->layoutFactory = $layoutFactory;
    }

    /**
     * Filter category data.
     *
     * @param array $rawData
     *
     * @return array
     */
    protected function _filterCategoryPostData(array $rawData)
    {
        $data = $rawData;
        // @todo It is a workaround to prevent saving this data in category model and it has to be refactored in future
        if (isset($data['image']) && is_array($data['image'])) {
            if (!empty($data['image']['delete'])) {
                $data['image'] = null;
            } else {
                if (isset($data['image'][0]['name']) && isset($data['image'][0]['tmp_name'])) {
                    $data['image'] = $data['image'][0]['name'];
                } else {
                    unset($data['image']);
                }
            }
        }

        $data['vendor_id'] = $this->getVendor()->getId();

        return $data;
    }

    /**
     * Category save.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $category = $this->_initCategory();

        if (!$category) {
            return $resultRedirect->setPath('catalog/*/', ['_current' => true, 'id' => null]);
        }

       // $refreshTree = false;
        $data = $this->getRequest()->getPostValue(); //var_dump($data);die();
        $categoryPostData = $data;

        $isNewCategory = !isset($categoryPostData['category_id']);

        $categoryPostData = $this->imagePreprocessing($categoryPostData);
        $parentId = isset($categoryPostData['parent']) ? $categoryPostData['parent'] : null;
        if ($categoryPostData) {
            $category->addData($this->_filterCategoryPostData($categoryPostData));
            if ($isNewCategory) {
                $parentCategory = $this->getParentCategory($parentId);
                $category->setPath($parentCategory->getPath());
                $category->setParentId($parentCategory->getId());
            }

            if (isset($categoryPostData['category_products'])
                && is_string($categoryPostData['category_products'])
            ) {
                $products = json_decode($categoryPostData['category_products'], true);
                $category->setPostedProducts($products);
            }
            $this->_eventManager->dispatch(
                'vendors_category_prepare_save',
                ['category' => $category, 'request' => $this->getRequest()]
            );
            $category->generalUrlKey();
            try {
                $validate = $category->validate();
                if ($validate !== true) {
                    foreach ($validate as $code => $error) {
                        if ($error === true) {
                            throw new \Magento\Framework\Exception\LocalizedException(
                                __('Attribute "%1" is required.', $error)
                            );
                        } else {
                            throw new \Exception($error);
                        }
                    }
                }

                $category->save();
                $this->messageManager->addSuccess(__('You saved the category.'));
                $refreshTree = true;
            } catch (\Magento\Framework\Exception\AlreadyExistsException $e) {
                $this->messageManager->addError($e->getMessage());
                $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
                $this->_getSession()->setCategoryData($categoryPostData);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
                $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
                $this->_getSession()->setCategoryData($categoryPostData);
            } catch (\Exception $e) {
                $this->messageManager->addError(__('Something went wrong while saving the category.'));
                $this->messageManager->addError($e->getMessage());
                $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
                $this->_getSession()->setCategoryData($categoryPostData);
            }
        }

        $hasError = (bool) $this->messageManager->getMessages()->getCountByType(
            \Magento\Framework\Message\MessageInterface::TYPE_ERROR
        );

        if ($this->getRequest()->getPost('return_session_messages_only')) {
            $category->load($category->getId());
            // to obtain truncated category name
            /** @var $block \Magento\Framework\View\Element\Messages */
            $block = $this->layoutFactory->create()->getMessagesBlock();
            $block->setMessages($this->messageManager->getMessages(true));

            /** @var \Magento\Framework\Controller\Result\Json $resultJson */
            $resultJson = $this->resultJsonFactory->create();

            return $resultJson->setData(
                [
                    'messages' => $block->getGroupedHtml(),
                    'error' => $hasError,
                    'category' => $category->toArray(),
                ]
            );
        }

        $redirectParams = $this->getRedirectParams($isNewCategory, $hasError, $category->getId(), $parentId);

        return $resultRedirect->setPath(
            $redirectParams['path'],
            $redirectParams['params']
        );
    }

    /**
     * Image data preprocessing.
     *
     * @param array $data
     *
     * @return array
     */
    public function imagePreprocessing($data)
    {
        if (empty($data['image'])) {
            unset($data['image']);
            $data['image']['delete'] = true;
        }

        return $data;
    }

    /**
     * Converting inputs from string to boolean.
     *
     * @param array $data
     * @param array $stringToBoolInputs
     *
     * @return array
     */
    public function stringToBoolConverting($data, $stringToBoolInputs = null)
    {
        if (null === $stringToBoolInputs) {
            $stringToBoolInputs = $this->stringToBoolInputs;
        }
        foreach ($stringToBoolInputs as $key => $value) {
            if (is_array($value)) {
                if (isset($data[$key])) {
                    $data[$key] = $this->stringToBoolConverting($data[$key], $value);
                }
            } else {
                if (isset($data[$value])) {
                    if ($data[$value] === 'true') {
                        $data[$value] = true;
                    }
                    if ($data[$value] === 'false') {
                        $data[$value] = false;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Get parent category.
     *
     * @param int $parentId
     *
     * @return \Magento\Catalog\Model\Category
     */
    protected function getParentCategory($parentId)
    {
        if (!$parentId) {
            $parentId = $this->_objectManager->create(\Vnecoms\VendorsCategory\Model\Category::class)
                ->getRootId($this->getVendor()->getId());
        }

        return $this->_objectManager->create(\Vnecoms\VendorsCategory\Model\Category::class)->load($parentId);
    }

    /**
     * Get category redirect path.
     *
     * @param bool $isNewCategory
     * @param bool $hasError
     * @param int  $categoryId
     * @param int  $parentId
     *
     * @return array
     */
    protected function getRedirectParams($isNewCategory, $hasError, $categoryId, $parentId)
    {
        $params = ['_current' => true];
        if ($isNewCategory && $hasError) {
            $path = 'catalog/*/add';
            $params['parent'] = $parentId;
        } else {
            $path = 'catalog/*/edit';
            $params['id'] = $categoryId;
        }

        return ['path' => $path, 'params' => $params];
    }

    /**
     * Upload template image.
     */
    public function uploadTemplateImages()
    {
        $image = '';
        try {
                $uploader = $this->_objectManager->create(
                    'Magento\Framework\File\Uploader',
                    ['fileId' => 'images']
                );
                $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);
                $uploader->setAllowRenameFiles(true);
                $uploader->setFilesDispersion(true);
                $mediaDirectory = $this->_objectManager->get('Magento\Framework\Filesystem')
                    ->getDirectoryRead(DirectoryList::MEDIA);
                $result = $uploader->save($mediaDirectory->getAbsolutePath('ves_vendorscategory/'));
                unset($result['tmp_name']);
                unset($result['path']);
                $image = $result['file'];
                $this->resizeImage($image, 'images');
                $this->customResizeImage($image, 'images');
        } catch (\Exception $e) {
            $image = '';
            $this->messageManager->addError($e->getMessage());
        }

        return $image;
    }

    public function deleteImageFile($image)
    {
    }
}
