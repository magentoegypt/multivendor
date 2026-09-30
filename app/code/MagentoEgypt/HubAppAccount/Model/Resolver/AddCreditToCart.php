<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppAccount\Model\Resolver;

use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Cart\AddProductsToCartError;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteMutexInterface;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use MagentoEgypt\HubAppAccount\Model\Credit\TopUpCatalog;
use MagentoEgypt\HubAppAccount\Model\Credit\TopUpOptions;
use Psr\Log\LoggerInterface;

/**
 * Mutation.hmAddCreditToCart — buy store credit: one line of the website's credit product for the amount,
 * built the way its product page posts it (TopUpOptions::buyRequest), added the way core adds products.
 *
 *   - signed-in customers only, on their own cart: GetCartForUser refuses anyone else's cart (as the other
 *     cart mutations do) and the cart is locked (QuoteMutex) while the line is added. The website never
 *     lets a guest check out a credit product (Vnecoms_Credit's checkout_allow_guest observer), and the
 *     credit is paid into the account of the order's customer when its invoice is paid;
 *   - the amount must be one on offer: a preset, or a whole number within the custom range. The product
 *     must still be one the store view sells (TopUpCatalog: approved, active seller, enabled, visible,
 *     in stock);
 *   - product-level problems come back in user_errors with core's error codes and the cart unchanged;
 *     cart-level ones (unknown cart, not yours) throw.
 *
 * Returns AddProductsToCartOutput like addProductsToCart.
 */
class AddCreditToCart implements ResolverInterface
{
    private const ERROR_PRODUCT_NOT_FOUND = 'PRODUCT_NOT_FOUND';
    private const ERROR_INVALID_VALUE = 'INVALID_PARAMETER_VALUE';

    public function __construct(
        private readonly GetCartForUser $getCartForUser,
        private readonly QuoteMutexInterface $quoteMutex,
        private readonly TopUpCatalog $catalog,
        private readonly CartRepositoryInterface $cartRepository,
        private readonly AddProductsToCartError $errorFactory,
        private readonly DataObjectFactory $dataObjectFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        $customerId = Caller::customerId($context);
        $input = (array) ($args['input'] ?? []);
        $maskedCartId = trim((string) ($input['cart_id'] ?? ''));
        if ($maskedCartId === '') {
            throw new GraphQlInputException(__('Required parameter "cart_id" is missing'));
        }

        return $this->add($maskedCartId, $customerId, Caller::storeId($context), (float) ($input['amount'] ?? 0));
    }

    /**
     * Adds $amount of credit to customer $customerId's cart $maskedCartId.
     *
     * @return array{cart: array{model: Quote}, user_errors: array<int, array<string, mixed>>}
     */
    public function add(string $maskedCartId, int $customerId, int $storeId, float $amount): array
    {
        //  Refuse someone else's cart before taking its lock.
        $this->getCartForUser->execute($maskedCartId, $customerId, $storeId);

        return $this->quoteMutex->execute(
            [$maskedCartId],
            \Closure::fromCallable([$this, 'run']),
            [$maskedCartId, $customerId, $storeId, $amount]
        );
    }

    /**
     * @return array{cart: array{model: Quote}, user_errors: array<int, array<string, mixed>>}
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function run(string $maskedCartId, int $customerId, int $storeId, float $amount): array
    {
        $cart = $this->getCartForUser->execute($maskedCartId, $customerId, $storeId);
        $cartStoreId = (int) $cart->getStoreId();
        $options = $this->catalog->forStore($cartStoreId);
        if ($options === null) {
            return $this->output($cart, [
                $this->error(self::ERROR_PRODUCT_NOT_FOUND, (string) __('Store credit can\'t be bought at the moment.')),
            ]);
        }
        $choice = $options->buyRequest($amount);
        if ($choice === null) {
            return $this->output($cart, [
                $this->error(self::ERROR_INVALID_VALUE, $this->amountsOnOffer($options, $cartStoreId)),
            ]);
        }
        $product = $this->catalog->productForCart($choice['product_id'], $cartStoreId);
        if ($product === null) {
            return $this->output($cart, [
                $this->error(self::ERROR_PRODUCT_NOT_FOUND, (string) __('Store credit can\'t be bought at the moment.')),
            ]);
        }

        $messages = [];
        try {
            $result = $cart->addProduct($product, $this->dataObjectFactory->create(['data' => $choice['request']]));
            if (is_string($result)) {
                $messages = array_values(array_unique(array_filter(explode("\n", $result))));
            }
        } catch (LocalizedException $e) {
            $messages[] = $e->getMessage();
        } catch (\Throwable $e) {
            $this->logger->error(sprintf(
                'HubAppAccount: hmAddCreditToCart failed for %s (%s): %s',
                $choice['sku'],
                $amount,
                $e->getMessage()
            ));
            $messages[] = (string) __('We couldn\'t add the credit to your cart. Please try again.');
        }

        if ($messages) {
            //  Undo what the failed add left on the in-memory cart, as core does.
            $cart->getItemsCollection()->clear();
            $errors = [];
            foreach ($messages as $message) {
                $errors[] = $this->error($this->errorFactory->create((string) $message)->getCode(), (string) $message);
            }

            return $this->output($cart, $errors);
        }

        $this->cartRepository->save($cart);

        return $this->output($cart, []);
    }

    /**
     * What can be bought, worded for the customer.
     */
    private function amountsOnOffer(TopUpOptions $options, int $storeId): string
    {
        $min = $options->min();
        $max = $options->max();
        if ($min === null || $max === null) {
            return (string) __('Choose one of the credit amounts on offer.');
        }
        $currency = $this->catalog->currency($storeId);
        $from = sprintf('%s %s', $currency, number_format($min, 0, '.', ','));
        $to = sprintf('%s %s', $currency, number_format($max, 0, '.', ','));

        return $options->presets()
            ? (string) __('Choose one of the credit amounts on offer, or a whole amount from %1 to %2.', $from, $to)
            : (string) __('Enter a whole amount from %1 to %2.', $from, $to);
    }

    /**
     * @return array{code: string, message: string, path: int[], quantity: null}
     */
    private function error(string $code, string $message): array
    {
        return ['code' => $code, 'message' => $message, 'path' => [0], 'quantity' => null];
    }

    /**
     * @param array<int, array<string, mixed>> $errors
     * @return array{cart: array{model: Quote}, user_errors: array<int, array<string, mixed>>}
     */
    private function output(Quote $cart, array $errors): array
    {
        $cart->setHasError(false);

        return ['cart' => ['model' => $cart], 'user_errors' => $errors];
    }
}
