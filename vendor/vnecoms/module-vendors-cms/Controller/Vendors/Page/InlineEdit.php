<?php

namespace Vnecoms\VendorsCms\Controller\Vendors\Page;

use Vnecoms\Vendors\App\Action\Context;
use Vnecoms\VendorsCms\Api\PageRepositoryInterface as PageRepository;
use Magento\Framework\Controller\Result\JsonFactory;
use Vnecoms\VendorsCms\Api\Data\PageInterface;

/**
 * Cms page grid inline edit controller.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class InlineEdit extends \Vnecoms\Vendors\Controller\Vendors\Action
{
    /**
     * Authorization level of a basic admin session.
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsCms::page';
    /** @var PostDataProcessor */
    protected $dataProcessor;

    /** @var PageRepository  */
    protected $pageRepository;

    /** @var JsonFactory  */
    protected $jsonFactory;

    /**
     * @param Context           $context
     * @param PostDataProcessor $dataProcessor
     * @param PageRepository    $pageRepository
     * @param JsonFactory       $jsonFactory
     */
    public function __construct(
        Context $context,
        PostDataProcessor $dataProcessor,
        PageRepository $pageRepository,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
        $this->dataProcessor = $dataProcessor;
        $this->pageRepository = $pageRepository;
        $this->jsonFactory = $jsonFactory;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->jsonFactory->create();
        $error = false;
        $messages = [];

        $postItems = $this->getRequest()->getParam('items', []);
        if (!($this->getRequest()->getParam('isAjax') && count($postItems))) {
            return $resultJson->setData([
                'messages' => [__('Please correct the data sent.')],
                'error' => true,
            ]);
        }

        foreach (array_keys($postItems) as $pageId) {
            /** @var \Vnecoms\VendorsCms\Model\Page $page */
            $page = $this->pageRepository->getById($pageId);
            try {
                $pageData = $this->filterPost($postItems[$pageId]);
                $this->validatePost($pageData, $page, $error, $messages);
                $extendedPageData = $page->getData();
                $this->setCmsPageData($page, $extendedPageData, $pageData);
                $this->pageRepository->save($page);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $messages[] = $this->getErrorWithPageId($page, $e->getMessage());
                $error = true;
            } catch (\RuntimeException $e) {
                $messages[] = $this->getErrorWithPageId($page, $e->getMessage());
                $error = true;
            } catch (\Exception $e) {
                $messages[] = $this->getErrorWithPageId(
                    $page,
                    __('Something went wrong while saving the page.')
                );
                $error = true;
            }
        }

        return $resultJson->setData([
            'messages' => $messages,
            'error' => $error,
        ]);
    }

    /**
     * Filtering posted data.
     *
     * @param array $postData
     *
     * @return array
     */
    protected function filterPost($postData = [])
    {
        $pageData = $this->dataProcessor->filter($postData);
        $pageData['custom_theme'] = isset($pageData['custom_theme']) ? $pageData['custom_theme'] : null;
        $pageData['custom_root_template'] = isset($pageData['custom_root_template'])
            ? $pageData['custom_root_template']
            : null;

        return $pageData;
    }

    /**
     * Validate post data.
     *
     * @param array                          $pageData
     * @param \Vnecoms\VendorsCms\Model\Page $page
     * @param bool                           $error
     * @param array                          $messages
     */
    protected function validatePost(array $pageData, \Vnecoms\VendorsCms\Model\Page $page, &$error, array &$messages)
    {
        if (!($this->dataProcessor->validate($pageData) && $this->dataProcessor->validateRequireEntry($pageData))) {
            $error = true;
            foreach ($this->messageManager->getMessages(true)->getItems() as $error) {
                $messages[] = $this->getErrorWithPageId($page, $error->getText());
            }
        }
    }

    /**
     * Add page title to error message.
     *
     * @param PageInterface $page
     * @param string        $errorText
     *
     * @return string
     */
    protected function getErrorWithPageId(PageInterface $page, $errorText)
    {
        return '[Page ID: '.$page->getId().'] '.$errorText;
    }

    /**
     * Set cms page data.
     *
     * @param \Vnecoms\VendorsCms\Model\Page $page
     * @param array                          $extendedPageData
     * @param array                          $pageData
     *
     * @return $this
     */
    public function setCmsPageData(\Vnecoms\VendorsCms\Model\Page $page, array $extendedPageData, array $pageData)
    {
        $page->setData(array_merge($page->getData(), $extendedPageData, $pageData));

        return $this;
    }
}
