<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppBundle\Model\Resolver;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Cart\AddProductsToCartError;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteMutexInterface;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use MagentoEgypt\HubAppBundle\Model\Cart\BundleBuyRequestBuilder;
use MagentoEgypt\HubAppBundle\Model\Cart\BundleSelectionReader;
use MagentoEgypt\HubAppBundle\Model\Cart\MarketplaceGate;
use MagentoEgypt\HubAppBundle\Model\Cart\SuperAttributeKeeper;
use Psr\Log\LoggerInterface;

/**
 * Mutation.hmAddBundleToCart — add one bundle or new_bundle line, configurable choices included.
 *
 * Core's addProductsToCart cannot say which variant of a configurable product
 * inside a bundle the customer chose (selected_options carries one flat list),
 * and new_bundle offers exactly that. This mutation builds the buy request the
 * website's bundle page posts (BundleBuyRequestBuilder) and adds it the way
 * core does:
 *
 *   - the cart is the caller's: GetCartForUser refuses another customer's cart
 *     and a guest reaching a customer cart; the cart is locked (QuoteMutex)
 *     while the line is added;
 *   - the product must be a bundle or new_bundle of this website, saleable;
 *   - the marketplace's add-to-cart rule, which the website applies in an
 *     observer of checkout_cart_product_add_before that Quote::addProduct()
 *     never triggers (MarketplaceGate): the bundle approved, of an active
 *     seller and shown in the store view (else "not found"), every selected
 *     product approved and of an active seller;
 *   - product-level problems come back in user_errors with core's error codes
 *     and the cart unchanged; cart-level ones (unknown cart, not yours) throw.
 *
 * Returns AddProductsToCartOutput like addProductsToCart.
 */
class AddBundleToCart implements ResolverInterface
{
    private const BUNDLE_TYPES = ['bundle', 'new_bundle'];

    private const ERROR_PRODUCT_NOT_FOUND = 'PRODUCT_NOT_FOUND';
    private const ERROR_INVALID_VALUE = 'INVALID_PARAMETER_VALUE';

    public function __construct(
        private readonly GetCartForUser $getCartForUser,
        private readonly QuoteMutexInterface $quoteMutex,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly CartRepositoryInterface $cartRepository,
        private readonly BundleSelectionReader $selectionReader,
        private readonly BundleBuyRequestBuilder $buyRequestBuilder,
        private readonly SuperAttributeKeeper $superAttributeKeeper,
        private readonly MarketplaceGate $marketplaceGate,
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
        $input = (array) ($args['input'] ?? []);
        $maskedCartId = trim((string) ($input['cart_id'] ?? ''));
        if ($maskedCartId === '') {
            throw new GraphQlInputException(__('Required parameter "cart_id" is missing'));
        }
        if (trim((string) ($input['sku'] ?? '')) === '') {
            throw new GraphQlInputException(__('Required parameter "sku" is missing'));
        }

        $storeId = (int) $context->getExtensionAttributes()->getStore()->getId();
        //  Refuse someone else's cart before taking its lock.
        $this->getCartForUser->execute($maskedCartId, $context->getUserId(), $storeId);

        return $this->quoteMutex->execute(
            [$maskedCartId],
            \Closure::fromCallable([$this, 'run']),
            [$context, $input, $maskedCartId, $storeId]
        );
    }

    /**
     * @param mixed $context
     * @param array<string, mixed> $input
     * @return array{cart: array{model: Quote}, user_errors: array<int, array<string, mixed>>}
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function run($context, array $input, string $maskedCartId, int $storeId): array
    {
        $cart = $this->getCartForUser->execute($maskedCartId, $context->getUserId(), $storeId);
        $sku = trim((string) $input['sku']);
        $quantity = isset($input['quantity']) ? (float) $input['quantity'] : 1.0;

        $product = $this->loadBundle($sku, $cart);
        //  Unapproved, or its seller inactive: as unknown as on the website.
        if ($product === null || !$this->marketplaceGate->allowsBundle((int) $product->getId(), $storeId)) {
            return $this->output($cart, [
                $this->error(
                    self::ERROR_PRODUCT_NOT_FOUND,
                    (string) __('Could not find a product with SKU "%sku"', ['sku' => $sku])
                ),
            ]);
        }
        if (!in_array((string) $product->getData('type_id'), self::BUNDLE_TYPES, true)) {
            return $this->output($cart, [
                $this->error(self::ERROR_INVALID_VALUE, (string) __('Product "%1" is not a bundle.', $sku)),
            ]);
        }

        $bundleSelections = $this->selectionReader->forBundle($product);
        try {
            $request = $this->buyRequestBuilder->build(
                (int) $product->getId(),
                $quantity,
                array_values((array) ($input['selections'] ?? [])),
                $bundleSelections
            );
        } catch (LocalizedException $e) {
            return $this->output($cart, [$this->error(self::ERROR_INVALID_VALUE, $e->getMessage())]);
        }
        if (!$this->marketplaceGate->allowsSelections(MarketplaceGate::selectedProductIds($request, $bundleSelections))) {
            return $this->output($cart, [
                $this->error(self::ERROR_INVALID_VALUE, (string) __('The options you selected are not available.')),
            ]);
        }

        $buyRequest = $this->dataObjectFactory->create(['data' => $request]);
        $this->superAttributeKeeper->remember($buyRequest);
        $messages = [];
        try {
            $result = $cart->addProduct($product, $buyRequest);
            if (is_string($result)) {
                $messages = array_values(array_unique(array_filter(explode("\n", $result))));
            }
        } catch (LocalizedException $e) {
            $messages[] = $e->getMessage();
        } catch (\Throwable $e) {
            $this->logger->error('HubApp: hmAddBundleToCart failed for ' . $sku . ': ' . $e->getMessage());
            $messages[] = (string) __('Something went wrong while adding the bundle to the cart.');
        } finally {
            $this->superAttributeKeeper->forget($buyRequest);
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
     * The bundle as a fresh, private copy for this store view, or null when the
     * cart's website cannot sell it.
     */
    private function loadBundle(string $sku, Quote $cart): ?Product
    {
        try {
            $loaded = $this->productRepository->get($sku, false, (int) $cart->getStoreId(), true);
        } catch (NoSuchEntityException $e) {
            return null;
        }
        if (!$loaded instanceof Product) {
            return null;
        }
        //  prepareForCart() adds custom options to the product: never on the repository's shared copy.
        $product = clone $loaded;

        $websiteId = (int) $cart->getStore()->getWebsiteId();
        if (!in_array($websiteId, array_map('intval', (array) $product->getWebsiteIds()), true)
            || !$product->isSaleable()
            || !$product->isAvailable()
        ) {
            return null;
        }

        return $product;
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
