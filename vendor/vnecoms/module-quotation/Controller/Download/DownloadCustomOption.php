<?php
namespace Vnecoms\Quotation\Controller\Download;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Action\Context;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Framework\Controller\Result\ForwardFactory;

/**
 * Class DownloadCustomOption
 *
 * @package Magento\Sales\Controller\Download
 */
class DownloadCustomOption extends \Magento\Framework\App\Action\Action implements HttpGetActionInterface
{
    /**
     * @var ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @var \Magento\Sales\Model\Download
     */
    protected $download;

    /**
     * @var \Magento\Framework\Unserialize\Unserialize
     * @deprecated 101.0.0
     */
    protected $unserialize;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializer;

    /**
     * @param Context $context
     * @param ForwardFactory $resultForwardFactory
     * @param \Magento\Sales\Model\Download $download
     * @param \Magento\Framework\Unserialize\Unserialize $unserialize
     * @param \Magento\Framework\Serialize\Serializer\Json $serializer
     */
    public function __construct(
        Context $context,
        ForwardFactory $resultForwardFactory,
        \Magento\Sales\Model\Download $download,
        \Magento\Framework\Unserialize\Unserialize $unserialize,
        \Magento\Framework\Serialize\Serializer\Json $serializer = null
    ) {
        parent::__construct($context);
        $this->resultForwardFactory = $resultForwardFactory;
        $this->download = $download;
        $this->unserialize = $unserialize;
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(
            \Magento\Framework\Serialize\Serializer\Json::class
        );
    }

    /**
     * Custom options download action
     *
     * @return void|\Magento\Framework\Controller\Result\Forward
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        $quoteItemId = $this->getRequest()->getParam('id');
        /** @var $option \Vnecoms\Quotation\Model\Item */
        $item = $this->_objectManager->create(
            \Vnecoms\Quotation\Model\Item::class
        )->load($quoteItemId);
        /** @var \Magento\Framework\Controller\Result\Forward $resultForward */
        $resultForward = $this->resultForwardFactory->create();

        if (!$item->getId()) {
            return $resultForward->forward('noroute');
        }

        $optionId = $this->getRequest()->getParam('option_id');
				$buyRequest = $item->getBuyRequest();
				$options = $buyRequest->getOptions();
				$option = isset($options[$optionId])?$options[$optionId]:null;
				if(!$option){
					 return $resultForward->forward('noroute');
				}

        try {
            $this->download->downloadFile($option);
        } catch (\Exception $e) {
            return $resultForward->forward('noroute');
        }
        $this->endExecute();
    }

    /**
     * Ends execution process
     *
     * @return void
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    protected function endExecute()
    {
        exit(0);
    }
}
