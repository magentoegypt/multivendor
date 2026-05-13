<?php

namespace Vnecoms\VendorsCategory\Controller\Vendors\Category;

/**
 * Catalog category validate.
 */
class Validate extends \Vnecoms\VendorsCategory\Controller\Vendors\Category
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * Validate constructor.
     *
     * @param \Vnecoms\Vendors\App\Action\Context              $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    public function __construct(
        \Vnecoms\Vendors\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    /**
     * AJAX category validation action.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $response = new \Magento\Framework\DataObject();
        $response->setError(0);

        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setData($response);

        return $resultJson;
    }
}
