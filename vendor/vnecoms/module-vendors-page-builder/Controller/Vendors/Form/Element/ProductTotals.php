<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Vnecoms\VendorsPageBuilder\Controller\Vendors\Form\Element;

use Exception;
use Vnecoms\Vendors\App\Action\Context;
use Vnecoms\Vendors\Controller\Vendors\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Returns the number of products that match the provided conditions
 */
class ProductTotals extends Action implements HttpPostActionInterface
{
    /**
     * @var \Vnecoms\VendorsPageBuilder\Model\Catalog\ProductTotals
     */
    private $productTotals;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @param Context $context
     * @param \Vnecoms\VendorsPageBuilder\Model\Catalog\ProductTotals $productTotals
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Context $context,
        \Vnecoms\VendorsPageBuilder\Model\Catalog\ProductTotals $productTotals,
        JsonFactory $jsonFactory
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->productTotals = $productTotals;
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $conditions = $this->getRequest()->getParam('conditionValue');

        if (!$conditions) {
            return $this->jsonFactory->create()->setData([]);
        }

        try {
            $response = $this->productTotals->getProductTotals($conditions);
        } catch (Exception $e) {
            $response = [
                'total' => 0,
                'disabled' => 0,
                'notVisible' => 0,
            ];
        }

        return $this->jsonFactory->create()->setData($response);
    }
}
