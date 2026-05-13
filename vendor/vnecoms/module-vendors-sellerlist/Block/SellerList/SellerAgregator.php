<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Vnecoms\VendorsSellerList\Block\SellerList;

use \Vnecoms\VendorsSellerList\Block\Seller as SellerBlock;
use \Magento\Catalog\Helper\Product as CatalogProductHelper;
use \Magento\Catalog\Block\Product\ReviewRendererInterface;
use \Magento\Framework\Stdlib\StringUtils;
use \Magento\Framework\Url\Helper\Data as UrlHelper;
use \Magento\Framework\Data\Form\FormKey;

/**
 * SellerAgregator class for autocomplete data
 *
 * @method Seller setSeller(\Vnecoms\Vendors\Model\Vendor $seller)
 */
class SellerAgregator extends \Magento\Framework\DataObject
{
    /**
     * @var \Vnecoms\VendorsSellerList\Block\Seller
     */
    protected $sellerBlock;

    /**
     * @var \Magento\Framework\Url\Helper\Data
     */
    protected $urlHelper;

    /**
     * @var \Magento\Framework\Data\Form\FormKey
     */
    protected $formKey;
    

    public function __construct(
        SellerBlock $sellerBlock,
        CatalogProductHelper $catalogHelperProduct,
        StringUtils $string,
        UrlHelper $urlHelper,
        FormKey $formKey
    ) {
        $this->sellerBlock = $sellerBlock;
        $this->catalogHelperProduct = $catalogHelperProduct;
        $this->string = $string;
        $this->urlHelper = $urlHelper;
        $this->formKey = $formKey;
    }

    /**
     * Retrieve product name
     *
     * @return string
     */
    public function getName()
    {
        return html_entity_decode($this->getSeller()->getName());
    }

    /**
     * Retrieve product sku
     *
     * @return string
     */
    public function getSku()
    {
        return $this->getProduct()->getSku();
    }

    /**
     * Retrieve product small image url
     *
     * @return bool|string
     */
    public function getSmallImage()
    {
        return $this->catalogHelperProduct->getSmallImageUrl($this->getProduct());
    }

    /**
     * Retrieve product reviews rating html
     *
     * @return string
     */
    public function getReviewsRating()
    {
        return $this->sellerBlock->getReviewsSummaryHtml(
            $this->getProduct(),
            ReviewRendererInterface::SHORT_VIEW,
            true
        );
    }

    /**
     * Retrieve product short description
     *
     * @return string
     */
    public function getShortDescription()
    {
        $shortDescription = $this->getProduct()->getShortDescription();

        return $this->cropDescription($shortDescription);
    }

    /**
     * Retrieve product description
     *
     * @return string
     */
    public function getDescription()
    {
        $description = $this->getProduct()->getDescription();

        return $this->cropDescription($description);
    }

    /**
     * Crop description to 50 symbols
     *
     * @param string|html $data
     * @return string
     */
    protected function cropDescription($data)
    {
        if (!$data) {
            return '';
        }

        $data = strip_tags($data);
        $data = (strlen($data) > 50) ? $this->string->substr($data, 0, 50) . '...' : $data;

        return $data;
    }

    /**
     * Retrieve product price
     *
     * @return string
     */
    public function getPrice()
    {
        return $this->sellerBlock->getProductPrice($this->getProduct(), \Magento\Catalog\Pricing\Price\FinalPrice::PRICE_CODE);
    }

    /**
     * Retrieve product url
     *
     * @param string $route
     * @param array $params
     * @return string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->sellerBlock->getProductUrl($this->getProduct());
    }

    /**
     * Retrieve product add to cart data
     *
     * @return array
     */
    public function getAddToCartData()
    {
        $formUrl = $this->sellerBlock->getAddToCartUrl($this->getProduct(), ['mageworx_searchsuiteautocomplete' => true]);
        $productId = $this->getProduct()->getEntityId();
        $paramNameUrlEncoded = \Magento\Framework\App\ActionInterface::PARAM_NAME_URL_ENCODED;
        $urlEncoded = $this->urlHelper->getEncodedUrl($formUrl);
        $formKey = $this->formKey->getFormKey();

        $addToCartData = [
            'formUrl' => $formUrl,
            'productId' => $productId,
            'paramNameUrlEncoded' => $paramNameUrlEncoded,
            'urlEncoded' => $urlEncoded,
            'formKey' => $formKey
        ];

        return $addToCartData;
    }
}
