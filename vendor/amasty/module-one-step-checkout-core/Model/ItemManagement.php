<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model;

use Amasty\CheckoutCore\Api\ItemManagementInterface;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\TotalsInterface;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Magento\Quote\Api\ShipmentEstimationInterface;
use Magento\Quote\Model\Cart\ShippingMethod;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Laminas\Uri\Uri;

class ItemManagement implements ItemManagementInterface
{
    private const GIFT_CARD_PRODUCT_TYPE = 'amgiftcard';

    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @var CartTotalRepositoryInterface
     */
    protected $cartTotalRepository;

    /**
     * @var CustomerCart
     */
    protected $cart;

    /**
     * @var TotalsFactory
     */
    protected $totalsFactory;

    /**
     * @var PaymentMethodManagementInterface
     */
    protected $paymentMethodManagement;

    /**
     * @var ShipmentEstimationInterface
     */
    protected $shipmentEstimation;

    /**
     * @var ItemResolverInterface
     */
    private $itemResolver;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var Uri
     */
    private $laminasUri;

    public function __construct(
        CartRepositoryInterface $cartRepository,
        CartTotalRepositoryInterface $cartTotalRepository,
        CustomerCart $cart,
        TotalsFactory $totalsFactory,
        ShipmentEstimationInterface $shipmentEstimation,
        PaymentMethodManagementInterface $paymentMethodManagement,
        ObjectManagerInterface $objectManager,
        Uri $laminasUri
    ) {
        $this->cartRepository = $cartRepository;
        $this->cartTotalRepository = $cartTotalRepository;
        $this->cart = $cart;
        $this->totalsFactory = $totalsFactory;
        $this->paymentMethodManagement = $paymentMethodManagement;
        $this->shipmentEstimation = $shipmentEstimation;
        $this->objectManager = $objectManager;
        $this->laminasUri = $laminasUri;

        if (interface_exists(ItemResolverInterface::class)) {
            $this->itemResolver = $this->objectManager->get(ItemResolverInterface::class);
        }
    }

    /**
     * @inheritdoc
     */
    public function remove($cartId, $itemId, AddressInterface $address)
    {
        /** @var Quote $quote */
        $quote = $this->cartRepository->get($cartId);
        $initialVirtualState = $quote->isVirtual();
        /** @var QuoteItem $item */
        $item = $quote->getItemById($itemId);

        if ($item && $item->getId()) {
            $quote->deleteItem($item);
            $this->cartRepository->save($quote);

            //Reload checkout to update 'Apply Gift Card Code' input's visibility
            if ($item->getProductType() === self::GIFT_CARD_PRODUCT_TYPE) {
                return false;
            }
        }

        if (!$initialVirtualState && $quote->isVirtual()) {
            return false;
        }

        /** @var ShippingMethod $shippingMethods */
        $shippingMethods = $this->shipmentEstimation->estimateByExtendedAddress($cartId, $address);
        /** @var Totals $totals */
        $totals = $this->totalsFactory->create(
            [
                'data' => [
                    'totals' => $this->cartTotalRepository->get($cartId),
                    'shipping' => $shippingMethods,
                    'payment' => $this->paymentMethodManagement->getList($cartId)
                ]
            ]
        );

        return $totals;
    }

    /**
     * @inheritdoc
     */
    public function update($cartId, $itemId, $formData)
    {
        /** @var Quote $quote */
        $quote = $this->cartRepository->get($cartId);
        $initialVirtualState = $quote->isVirtual();

        $this->cart->setQuote($quote);
        $params = $this->parseStr($formData);
        /** @var QuoteItem $item */
        $item = $this->cart->getQuote()->getItemById($itemId);

        if (!$item) {
            throw new LocalizedException(__('We can\'t find the quote item.'));
        }

        $params = $this->prepareParams($params, $itemId);

        $item = $this->cart->updateItem($itemId, new DataObject($params));
        if (is_string($item)) {
            throw new LocalizedException(__($item));
        }
        if ($item->getHasError()) {
            throw new LocalizedException(__($item->getMessage()));
        }

        $this->cart->save();

        if ($quote->isVirtual() && !$initialVirtualState) {
            return false;
        }

        /** @var TotalsInterface[] $items */
        $items = $this->cartTotalRepository->get($cartId);

        return $this->totalsFactory->create(['data' => [
            'totals' => $items,
            'payment' => $this->paymentMethodManagement->getList($cartId)
        ]]);
    }

    /**
     * @param string $str
     *
     * @return array
     */
    public function parseStr($str)
    {
        $this->laminasUri->setQuery($str);
        $params = $this->laminasUri->getQueryAsArray();

        return $params;
    }

    /**
     * @param array $params
     * @param int $itemId
     *
     * @return array
     */
    private function prepareParams($params, $itemId)
    {
        if (isset($params['qty'])) {
            $params['qty'] = (float)$params['qty'];
            $params['reset_count'] = true;
        }

        if (!isset($params['options'])) {
            $params['options'] = [];
        }

        $params['id'] = $itemId;

        return $params;
    }
}
