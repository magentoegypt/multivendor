<?php

namespace Vnecoms\Quotation\Block\Quotepage\Item\Renderer;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Message\InterpretationStrategyInterface;
use Magento\Catalog\Model\Config\Source\Product\Thumbnail as ThumbnailSource;
use Magento\Framework\DataObject\IdentityInterface;

class Configurable extends \Vnecoms\Quotation\Block\Quotepage\Item\Renderer implements IdentityInterface
{
    /**
     * Path in config to the setting which defines if parent or child product should be used to generate a thumbnail.
     */
    const CONFIG_THUMBNAIL_SOURCE = 'checkout/cart/configurable_product_image';


    public function __construct(\Magento\Framework\View\Element\Template\Context $context, \Magento\Catalog\Helper\Product\Configuration $productConfig, \Vnecoms\Quotation\Model\Session $session, \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder, \Magento\Framework\Url\Helper\Data $urlHelper, \Magento\Framework\Message\ManagerInterface $messageManager, PriceCurrencyInterface $priceCurrency, \Magento\Framework\Module\Manager $moduleManager, InterpretationStrategyInterface $messageInterpretationStrategy, \Magento\Framework\Stdlib\StringUtils $stringUtils, array $data = [])
    {
        parent::__construct($context, $productConfig, $session, $imageBuilder, $urlHelper, $messageManager, $priceCurrency, $moduleManager, $messageInterpretationStrategy, $stringUtils, $data);
    }

    /**
     * Get item configurable child product
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function getChildProduct()
    {
        if ($option = $this->getItem()->getOptionByCode('simple_product')) {
            return $option->getProduct();
        }
        return $this->getProduct();
    }

    /**
     * Get item product name
     *
     * @return string
     */
    public function getProductName()
    {
        return $this->getProduct()->getName();
    }

    /**
     * Get list of all options for product
     *
     * @return array
     */
    public function getOptionList()
    {
        return $this->_productConfig->getOptions($this->getItem());
    }

    /**
     * {@inheritdoc}
     */
    public function getProductForThumbnail()
    {
        /**
         * Show parent product thumbnail if it must be always shown according to the related setting in system config
         * or if child thumbnail is not available
         */
        if ($this->_scopeConfig->getValue(
                self::CONFIG_THUMBNAIL_SOURCE,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ) == ThumbnailSource::OPTION_USE_PARENT_IMAGE ||
            !($this->getChildProduct()->getThumbnail() && $this->getChildProduct()->getThumbnail() != 'no_selection')
        ) {
            $product = $this->getProduct();
        } else {
            $product = $this->getChildProduct();
        }
        return $product;
    }

    /**
     * Return identifiers for produced content
     *
     * @return array
     */
    public function getIdentities()
    {
        $identities = parent::getIdentities();
        if ($this->getItem()) {
            $identities = array_merge($identities, $this->getChildProduct()->getIdentities());
        }
        return $identities;
    }
}