<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package One Step Checkout Gift Wrap for Magento 2 (System)
 */

namespace Amasty\CheckoutGiftWrap\Model;

use Amasty\CheckoutCore\Api\FeeRepositoryInterface;
use Amasty\CheckoutCore\Api\GiftWrapProviderInterface;
use Amasty\CheckoutCore\Model\Fee;
use Amasty\CheckoutCore\Model\FeeFactory;
use Amasty\CheckoutGiftWrap\Api\GiftWrapInformationManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

class GiftWrapInformationManagement implements GiftWrapInformationManagementInterface
{
    /**
     * @var CartTotalRepositoryInterface
     */
    protected $cartTotalRepository;

    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @var FeeRepositoryInterface
     */
    protected $feeRepository;

    /**
     * @var Fee
     */
    protected $feeFactory;

    /**
     * @var GiftWrapProviderInterface
     */
    protected $giftWrapProvider;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    public function __construct(
        CartRepositoryInterface $cartRepository,
        CartTotalRepositoryInterface $cartTotalRepository,
        FeeRepositoryInterface $feeRepository,
        FeeFactory $feeFactory,
        GiftWrapProviderInterface $giftWrapProvider,
        StoreManagerInterface $storeManager
    ) {
        $this->cartTotalRepository = $cartTotalRepository;
        $this->cartRepository = $cartRepository;
        $this->feeRepository = $feeRepository;
        $this->feeFactory = $feeFactory;
        $this->giftWrapProvider = $giftWrapProvider;
        $this->storeManager = $storeManager;
    }

    /**
     * @param string $cartId
     * @param bool $checked
     *
     * @return \Magento\Quote\Api\Data\TotalsInterface
     */
    public function update($cartId, $checked)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->cartRepository->get($cartId);

        $fee = $this->feeRepository->getByQuoteId($quote->getId());

        if ($checked && !$fee->getId()) {
            $baseAmount = $this->giftWrapProvider->getGiftWrapFee();

            $store = $this->storeManager->getStore();
            $rate = $store->getBaseCurrency()->getRate($store->getCurrentCurrency());

            $fee = $this->feeFactory->create(['data' => [
                'quote_id' => $quote->getId(),
                'amount' => $baseAmount * $rate,
                'base_amount' => $baseAmount,
            ]])->setDataChanges(true);

            $this->feeRepository->save($fee);
        } elseif (!$checked && $fee->getId()) {
            $this->feeRepository->delete($fee);
        }

        $quote->collectTotals();

        return $this->cartTotalRepository->get($cartId);
    }
}
