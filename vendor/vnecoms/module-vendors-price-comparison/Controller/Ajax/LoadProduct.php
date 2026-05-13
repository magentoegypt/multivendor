<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsPriceComparison\Controller\Ajax;

use Magento\Framework\App\Action\Context;

class LoadProduct extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $_helper;

    /**
     * @var \Vnecoms\VendorsPriceComparison\Model\LoadProduct
     */
    protected $_loadProduct;


    /**
     * Reply constructor.
     * @param Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonFactory
     * @param \Magento\Framework\Json\Helper\Data $helper
     * @param \Vnecoms\VendorsPriceComparison\Model\LoadProduct $loadProduct
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Framework\Json\Helper\Data $helper,
        \Vnecoms\VendorsPriceComparison\Model\LoadProduct $loadProduct
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_helper = $helper;
        $this->_loadProduct = $loadProduct;
        $this->_resultJsonFactory = $jsonFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $dataPost = $this->_helper->jsonDecode($this->getRequest()->getContent());
        $response = [];
        try{
            $products = $this->_loadProduct->getProductComparison($dataPost);
            $response['error'] = false;
            $response['products'] = $products;
        }catch (\Exception $e){
            $response = [
                'error' => true,
                'msg' => $e->getMessage(),
            ];
        }
        $resultJson = $this->_resultJsonFactory->create();
        $resultJson->setData($response);
        return $resultJson;
    }

}
