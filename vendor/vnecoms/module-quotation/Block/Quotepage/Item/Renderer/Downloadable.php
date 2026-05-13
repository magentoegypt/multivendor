<?php

namespace Vnecoms\Quotation\Block\Quotepage\Item\Renderer;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Message\InterpretationStrategyInterface;

class Downloadable extends \Vnecoms\Quotation\Block\Quotepage\Item\Renderer
{
    /**
     * Downloadable catalog product configuration
     *
     * @var \Magento\Downloadable\Helper\Catalog\Product\Configuration
     */
    protected $_downloadableProductConfiguration = null;


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
        \Magento\Downloadable\Helper\Catalog\Product\Configuration $downloadableProductConfiguration,
        array $data = []
    )
    {
        $this->_downloadableProductConfiguration = $downloadableProductConfiguration;

        parent::__construct($context, $productConfig, $session, $imageBuilder, $urlHelper, $messageManager, $priceCurrency, $moduleManager, $messageInterpretationStrategy, $stringUtils, $data);
    }

    /**
     * Retrieves item links options
     *
     * @return array
     */
    public function getLinks()
    {
        if (!$this->getItem()) {
            return [];
        }
        return $this->_downloadableProductConfiguration->getLinks($this->getItem());
    }

    /**
     * Return title of links section
     *
     * @return string
     */
    public function getLinksTitle()
    {
        return $this->_downloadableProductConfiguration->getLinksTitle($this->getProduct());
    }

    /**
     * Get list of all options for product
     *
     * @return array
     */
    public function getOptionList()
    {
        return $this->_downloadableProductConfiguration->getOptions($this->getItem());
    }

    /**
     * Get list of all options for product
     * @param \Magento\Catalog\Model\Product\Configuration\Item\ItemInterface $item
     * @return array
     */
    public function getOption($item)
    {
        return $this->_downloadableProductConfiguration->getOptions($item);
    }

}