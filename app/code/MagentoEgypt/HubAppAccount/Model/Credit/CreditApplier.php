<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppAccount\Model\Credit;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Psr\Log\LoggerInterface;
use Vnecoms\Credit\Api\CreditManagementInterface;

/**
 * Store credit on the signed-in customer's cart.
 *
 * The cart comes from GetCartForUser, so only its owner can touch it. Applying follows the website
 * (Vnecoms\Credit\Controller\Cart\CreditPost with the cart block's group gate) rather than the REST
 * shortcut PUT /V1/carts/mine/credit, which skips the group check:
 *   canUseCredit(customer group) -> amount > 0 -> amount <= balance -> CreditManagement::set().
 * The credit total collector then caps the credit used at subtotal after discount + shipping + tax,
 * so asking for more than the order costs applies the order total (Cart.hm_store_credit.applied).
 *
 * Amounts from the app are in the cart's currency; Vnecoms stores base-currency amounts, converted
 * with the cart's rate (1 on Hub Market, which runs in AED only).
 */
class CreditApplier
{
    public function __construct(
        private readonly GetCartForUser $getCartForUser,
        private readonly CreditManagementInterface $creditManagement,
        private readonly CreditAccountReader $reader,
        private readonly CartRepositoryInterface $cartRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @throws GraphQlInputException
     */
    public function apply(string $maskedCartId, float $amount, int $customerId, int $storeId): Quote
    {
        if (!is_finite($amount) || $amount <= 0) {
            throw new GraphQlInputException(__('Enter an amount of store credit greater than 0.'));
        }
        $cart = $this->getCartForUser->execute($maskedCartId, $customerId, $storeId);
        if (!$this->reader->canUse($customerId)) {
            throw new GraphQlInputException(__('Store credit can\'t be used with your account.'));
        }
        if (!(int) $cart->getItemsCount()) {
            throw new GraphQlInputException(__('Your cart is empty.'));
        }

        $rate = (float) $cart->getBaseToQuoteRate();
        $rate = $rate > 0 ? $rate : 1.0;
        $baseAmount = round($amount / $rate, 4);
        $balance = $this->reader->balance($customerId);
        if ($baseAmount > $balance + 0.00001) {
            throw new GraphQlInputException(__(
                'You can use at most %1 of store credit.',
                sprintf('%s %s', number_format($balance * $rate, 2, '.', ''), (string) $cart->getQuoteCurrencyCode())
            ));
        }

        try {
            $this->creditManagement->set((int) $cart->getId(), $baseAmount);
        } catch (\Throwable $e) {
            $this->logger->warning(sprintf(
                'HubAppAccount: store credit %s not applied to cart %d: %s',
                $baseAmount,
                (int) $cart->getId(),
                $e->getMessage()
            ));
            throw new GraphQlInputException(__('We couldn\'t apply the store credit. Please try again.'));
        }

        return $this->recollected($cart);
    }

    /**
     * Stop using credit on the cart. Idempotent: an empty cart or one without credit is returned as is.
     *
     * @throws GraphQlInputException
     */
    public function remove(string $maskedCartId, int $customerId, int $storeId): Quote
    {
        $cart = $this->getCartForUser->execute($maskedCartId, $customerId, $storeId);
        if ((int) $cart->getItemsCount()) {
            try {
                $this->creditManagement->remove((int) $cart->getId());
            } catch (\Throwable $e) {
                $this->logger->warning(sprintf(
                    'HubAppAccount: store credit not removed from cart %d: %s',
                    (int) $cart->getId(),
                    $e->getMessage()
                ));
                throw new GraphQlInputException(__('We couldn\'t remove the store credit. Please try again.'));
            }
        }

        return $this->recollected($cart);
    }

    /**
     * The cart as CreditManagement left it (the quote repository hands out one instance per id).
     */
    private function recollected(Quote $cart): Quote
    {
        $quote = $this->cartRepository->get((int) $cart->getId());

        return $quote instanceof Quote ? $quote : $cart;
    }
}
