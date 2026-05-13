<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * WYSIWYG widget options form.
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */

namespace Vnecoms\VendorsCms\App\Page\Chooser;

class Product extends \Magento\Backend\Block\Template
{
    /**
     * @var \Magento\Framework\Data\Form\Element\Factory
     */
    protected $_elementFactory;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $_jsonEncoder;

    /**
     * @param \Magento\Backend\Block\Template\Context      $context
     * @param \Magento\Framework\Json\EncoderInterface     $jsonEncoder
     * @param \Magento\Framework\Data\Form\Element\Factory $elementFactory
     * @param array                                        $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\Data\Form\Element\Factory $elementFactory,
        array $data = []
    ) {
        $this->_jsonEncoder = $jsonEncoder;
        $this->_elementFactory = $elementFactory;
        parent::__construct($context, $data);
    }
}
