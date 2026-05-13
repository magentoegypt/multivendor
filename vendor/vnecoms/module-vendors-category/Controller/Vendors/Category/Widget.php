<?php

namespace Vnecoms\VendorsCategory\Controller\Vendors\Category;

use Magento\Framework\View\Element\BlockInterface;

/**
 * Catalog category widgets controller for CMS WYSIWYG.
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
abstract class Widget extends \Vnecoms\VendorsCategory\Controller\Vendors\Category
{
    /**
     * @var \Magento\Framework\View\LayoutFactory
     */
    protected $layoutFactory;

    /**
     * Widget constructor.
     *
     * @param \Vnecoms\Vendors\App\Action\Context   $context
     * @param \Magento\Framework\View\LayoutFactory $layoutFactory
     */
    public function __construct(
        \Vnecoms\Vendors\App\Action\Context $context,
        \Magento\Framework\View\LayoutFactory $layoutFactory
    ) {
        parent::__construct($context);
        $this->layoutFactory = $layoutFactory;
    }

    /**
     * @return BlockInterface
     */
    protected function _getCategoryTreeBlock()
    {
        return $this->layoutFactory->create()->createBlock(
            'Vnecoms\VendorsCategory\Block\Vendors\Category\Widget\Chooser',
            '',
            [
                'data' => [
                    'id' => $this->getRequest()->getParam('uniq_id'),
                    'use_massaction' => $this->getRequest()->getParam('use_massaction', false),
                ],
            ]
        );
    }
}
