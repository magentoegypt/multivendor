<?php

namespace Vnecoms\VendorsCms\Controller\Vendors\System\Variable;

class WysiwygPlugin extends \Vnecoms\Vendors\Controller\Vendors\Action
{
    /**
     * Authorization level of a basic admin session.
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsCms::index';

    /** @var \Magento\Framework\Controller\Result\JsonFactory  */
    protected $resultJsonFactory;

    /**
     * WysiwygPlugin constructor.
     * @param \Vnecoms\Vendors\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    public function __construct(
        \Vnecoms\Vendors\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * WYSIWYG Plugin Action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $customVariables = $this->_objectManager->create('Magento\Variable\Model\Variable')
            ->getVariablesOptionArray(true);
        $storeContactVariabls = $this->_objectManager->create(
            'Magento\Variable\Model\Source\Variables'
        )->toOptionArray(
            true
        );
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData([$storeContactVariabls, $customVariables]);
    }
}
