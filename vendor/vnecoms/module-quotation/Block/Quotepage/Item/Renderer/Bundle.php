<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 */

namespace Vnecoms\Quotation\Block\Quotepage\Item\Renderer;


use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Message\InterpretationStrategyInterface;
use Magento\Bundle\Helper\Catalog\Product\Configuration;

class Bundle extends \Vnecoms\Quotation\Block\Quotepage\Item\Renderer
{
    /**
     * Bundle catalog product configuration
     *
     * @var Configuration
     */
    protected $_bundleProductConfiguration = null;


    public function __construct
    (
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Helper\Product\Configuration $productConfig,
        \Vnecoms\Quotation\Model\Session $session,
        \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Module\Manager $moduleManager,
        InterpretationStrategyInterface $messageInterpretationStrategy,
        \Magento\Framework\Stdlib\StringUtils $stringUtils,
        Configuration $configuration,
        array $data = []
    )
    {
        parent::__construct($context, $productConfig, $session, $imageBuilder, $urlHelper, $messageManager, $priceCurrency, $moduleManager, $messageInterpretationStrategy, $stringUtils, $data);
        $this->_bundleProductConfiguration = $configuration;
    }

    /**
     * Overloaded method for getting list of bundle options
     * Caches result in quote item, because it can be used in cart 'recent view' and on same page in cart checkout
     *
     * @return array
     */
    public function getOptionList()
    {
        return $this->_bundleProductConfiguration->getOptions($this->getItem());
    }
}