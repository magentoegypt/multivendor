<?php

namespace Algolia\AlgoliaSearch\Block\Cart;

use Algolia\AlgoliaSearch\Helper\ConfigHelper;
use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Recommend extends Template
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * @param Context $context
     * @param Session $checkoutSession
     * @param ConfigHelper $configHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        ConfigHelper $configHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
        $this->configHelper = $configHelper;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAllCartItems()
    {
        $cartItems = [];
        $itemCollection = $this->checkoutSession->getQuote()->getAllVisibleItems();
        foreach ($itemCollection as $item) {
            $cartItems[] = $item->getProductId();
        }
        return array_unique($cartItems);
    }

    /**
     * @return array
     */
    public function getAlgoliaRecommendConfiguration()
    {
        return [
            'enabledFBTInCart' => $this->configHelper->isRecommendFrequentlyBroughtTogetherEnabledOnCartPage(),
            'enabledRelatedInCart' => $this->configHelper->isRecommendRelatedProductsEnabledOnCartPage(),
            'isTrendItemsEnabledInCartPage' => $this->configHelper->isTrendItemsEnabledInShoppingCart()
             ];
    }
}