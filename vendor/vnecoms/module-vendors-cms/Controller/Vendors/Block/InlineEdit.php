<?php


namespace Vnecoms\VendorsCms\Controller\Vendors\Block;

use Vnecoms\Vendors\App\Action\Context;
use Vnecoms\VendorsCms\Api\BlockRepositoryInterface as BlockRepository;
use Magento\Framework\Controller\Result\JsonFactory;
use Vnecoms\VendorsCms\Api\Data\BlockInterface;

class InlineEdit extends \Vnecoms\Vendors\Controller\Vendors\Action
{
    /**
     * Authorization level of a basic admin session.
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsCms::block';
    /** @var BlockRepository  */
    protected $blockRepository;

    /** @var JsonFactory  */
    protected $jsonFactory;

    /**
     * @param Context         $context
     * @param BlockRepository $blockRepository
     * @param JsonFactory     $jsonFactory
     */
    public function __construct(
        Context $context,
        BlockRepository $blockRepository,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
        $this->blockRepository = $blockRepository;
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

        if ($this->getRequest()->getParam('isAjax')) {
            $postItems = $this->getRequest()->getParam('items', []);
            if (!count($postItems)) {
                $messages[] = __('Please correct the data sent.');
                $error = true;
            } else {
                foreach (array_keys($postItems) as $blockId) {
                    /** @var \Vnecoms\VendorsCms\Model\Block $block */
                    $block = $this->blockRepository->getById($blockId);
                    try {
                        $block->setData(array_merge($block->getData(), $postItems[$blockId]));
                        $this->blockRepository->save($block);
                    } catch (\Exception $e) {
                        $messages[] = $this->getErrorWithBlockId(
                            $block,
                            __($e->getMessage())
                        );
                        $error = true;
                    }
                }
            }
        }

        return $resultJson->setData([
            'messages' => $messages,
            'error' => $error,
        ]);
    }

    /**
     * Add block title to error message.
     *
     * @param BlockInterface $block
     * @param string         $errorText
     *
     * @return string
     */
    protected function getErrorWithBlockId(BlockInterface $block, $errorText)
    {
        return '[Block ID: '.$block->getId().'] '.$errorText;
    }
}
