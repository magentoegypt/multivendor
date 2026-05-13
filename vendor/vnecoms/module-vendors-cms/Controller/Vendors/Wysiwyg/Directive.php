<?php

namespace Vnecoms\VendorsCms\Controller\Vendors\Wysiwyg;

use Psr\Log\LoggerInterface;

class Directive extends \Vnecoms\Vendors\Controller\Vendors\Action
{
    /**
     * Authorization level of a basic admin session.
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsCms::wysiwyg_directive';
    /**
     * @var \Magento\Framework\Url\DecoderInterface
     */
    protected $urlDecoder;

    protected $logger;
    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /** @var \Vnecoms\VendorsCms\Model\Template\Filter  */
    protected $cmsFilter;


    /** @var \Vnecoms\VendorsCms\Model\Wysiwyg\Config  */
    protected $cmsWysiwygConfig;


    public function __construct(
        \Vnecoms\Vendors\App\Action\Context $context,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\Url\DecoderInterface $urlDecode,
        \Magento\Cms\Model\Template\Filter $cmsFilter,
        \Vnecoms\VendorsCms\Model\Wysiwyg\Config $cmsWysiwygConfig,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->resultRawFactory = $resultRawFactory;
        $this->urlDecoder = $urlDecode;
        $this->cmsFilter = $cmsFilter;
        $this->cmsWysiwygConfig = $cmsWysiwygConfig;
    }

    /**
     * Template directives callback.
     *
     * @return \Magento\Framework\Controller\Result\Raw
     */
    public function execute()
    {
        $directive = $this->getRequest()->getParam('___directive');
        $directive = $this->urlDecoder->decode($directive);
        $imagePath = $this->cmsFilter->filter($directive);
         /** @var \Magento\Framework\Image\Adapter\AdapterInterface $image */
        $image = $this->_objectManager->get(\Magento\Framework\Image\AdapterFactory::class)->create();
        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $resultRaw = $this->resultRawFactory->create();
        try {
            $image->open($imagePath);
            $resultRaw->setHeader('Content-Type', $image->getMimeType());
            $resultRaw->setContents($image->getImage());
        } catch (\Exception $e) {
            $imagePath = $this->cmsWysiwygConfig->getSkinImagePlaceholderPath();
            $image->open($imagePath);
            $resultRaw->setHeader('Content-Type', $image->getMimeType());
            $resultRaw->setContents($image->getImage());
            $this->logger->critical($e);
        }

        return $resultRaw;
    }
}
