<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Controller\Vendors\App\Product;

/**
 * Class Chooser
 * @package Vnecoms\VendorsCms\Controller\Vendors\App\Product
 */
class Chooser extends \Vnecoms\VendorsCms\Controller\Vendors\App
{
    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var \Magento\Framework\View\LayoutFactory
     */
    protected $layoutFactory;

    /**
     * Chooser constructor.
     * @param \Vnecoms\Vendors\App\Action\Context $context
     * @param \Vnecoms\VendorsCms\Model\AppFactory $appFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Math\Random $mathRandom
     * @param \Magento\Framework\Translate\InlineInterface $translateInline
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param \Magento\Framework\View\LayoutFactory $layoutFactory
     */
    public function __construct(
        \Vnecoms\Vendors\App\Action\Context $context,
        \Vnecoms\VendorsCms\Model\AppFactory $appFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Math\Random $mathRandom,
        \Magento\Framework\Translate\InlineInterface $translateInline,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\View\LayoutFactory $layoutFactory
    ) {
        $this->resultRawFactory = $resultRawFactory;
        $this->layoutFactory = $layoutFactory;
        parent::__construct($context, $appFactory, $logger, $mathRandom, $translateInline, $scopeConfig);
    }

    /**
     * Chooser Source action.
     *
     * @return \Magento\Framework\Controller\Result\Raw
     */
    public function execute()
    {
        $uniqId = $this->getRequest()->getParam('uniq_id');
        $massAction = $this->getRequest()->getParam('use_massaction', false);
        $productTypeId = $this->getRequest()->getParam('product_type_id', null);

        $layout = $this->layoutFactory->create();
        $productsGrid = $layout->createBlock(
            'Vnecoms\VendorsCms\Block\Vendors\Product\App\Chooser',
            '',
            [
                'data' => [
                    'id' => $uniqId,
                    'use_massaction' => $massAction,
                    'product_type_id' => $productTypeId,
                ],
            ]
        );

        $html = $productsGrid->toHtml();

        if (!$this->getRequest()->getParam('products_grid')) {
            $categoriesTree = $layout->createBlock(
                'Vnecoms\VendorsCms\Block\Vendors\Product\App\Chooser',
                '',
                [
                    'data' => [
                        'id' => $uniqId.'Tree',
                        'node_click_listener' => $productsGrid->getCategoryClickListenerJs(),
                        'with_empty_node' => true,
                    ],
                ]
            );

            $html = $layout->createBlock('Vnecoms\VendorsCms\Block\Vendors\Product\App\Chooser\Container')
                ->setTreeHtml($categoriesTree->toHtml())
                ->setGridHtml($html)
                ->toHtml();
        }

        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $resultRaw = $this->resultRawFactory->create();

        return $resultRaw->setContents($html);
    }
}
