<?php declare(strict_types=1);

namespace Mstore\Wishlist\Model;

use Exception;
use Magento\Catalog\Helper\Product as ProductHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Exception as ProductException;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Wishlist\Model\Item;
use Magento\Wishlist\Model\Item\OptionFactory;
use Magento\Wishlist\Model\ItemFactory;
use Magento\Wishlist\Model\LocaleQuantityProcessor;
use Magento\Wishlist\Model\ResourceModel\Item\Option\Collection;
use Magento\Wishlist\Model\ResourceModel\Wishlist as WishlistResourceModel;
use Magento\Wishlist\Model\Wishlist;
use Mstore\Wishlist\Api\Data\MessageBagInterface;
use Mstore\Wishlist\Api\WishlistManagementInterface;

class WishlistManagement implements WishlistManagementInterface
{
    /**
     * @var WishlistFactory
     */
    private $wishlistFactory;

    /**
     * @var WishlistResourceModel
     */
    private $wishlistResource;

    /**
     * @var ItemFactory
     */
    private $itemFactory;

    /**
     * @var WishlistProviderInterface
     */
    private $wishlistProvider;

    /**
     * @var LocaleQuantityProcessor
     */
    protected $quantityProcessor;

    /**
     * @var ProductHelper
     */
    private $productHelper;

    /**
     * @var OptionFactory
     */
    private $optionFactory;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var MessageBagInterface
     */
    private $messageBag;

    /**
     * WishlistRepository constructor.
     * @param WishlistFactory $wishlistFactory
     * @param ItemFactory $itemFactory
     * @param CartRepositoryInterface $quoteRepository
     * @param OptionFactory $optionFactory
     * @param ProductHelper $productHelper
     * @param WishlistProviderInterface $wishlistProvider
     * @param WishlistResourceModel $wishlistResource
     * @param MessageBagInterface $messageBag
     * @param LocaleQuantityProcessor $quantityProcessor
     */
    public function __construct(
        WishlistFactory $wishlistFactory,
        ItemFactory $itemFactory,
        CartRepositoryInterface $quoteRepository,
        OptionFactory $optionFactory,
        ProductHelper $productHelper,
        WishlistProviderInterface $wishlistProvider,
        WishlistResourceModel $wishlistResource,
        MessageBagInterface $messageBag,
        LocaleQuantityProcessor $quantityProcessor
    ) {
        $this->wishlistFactory = $wishlistFactory;
        $this->quoteRepository = $quoteRepository;
        $this->optionFactory = $optionFactory;
        $this->productHelper = $productHelper;
        $this->wishlistResource = $wishlistResource;
        $this->wishlistProvider = $wishlistProvider;
        $this->quantityProcessor = $quantityProcessor;
        $this->messageBag = $messageBag;
        $this->itemFactory = $itemFactory;
    }

    /**
     * @inheritDoc
     */
    public function addCart($customerId, $itemId, $qty = null): bool
    {
        /** @var  Quote $quote */
        $quote = $this->quoteRepository->getActiveForCustomer($customerId);
        return $this->moveToCart($quote, $itemId, $customerId, $qty);
    }

    /**
     * @inheritDoc
     */
    public function allCart($customerId, $qtys = null)
    {
        /** @var Wishlist $wishlist */
        $wishlist = $this->wishlistFactory->create();
        $wishlist->loadByCustomerId($customerId, true);
        return $this->moveAllToCart($wishlist, $customerId, $qtys);
    }

    /**
     * @param Quote $quote
     * @param $itemId
     * @param null $qty
     * @param null $customerId
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws ProductException
     * @throws Exception
     */
    private function moveToCart(Quote $quote, $itemId, $customerId, $qty = null)
    {
        /** @var Item $item */
        $item = $this->itemFactory->create();
        $item->load($itemId);
        if (!$item->getId()) {
            throw new LocalizedException(__('The wishlist item does not exist.'));
        }
        $wishlist = $this->wishlistProvider->getWishlist($item->getWishlistId(), $customerId);
        if (null === $qty) {
            $qty = 1;
        }

        $qty = $this->quantityProcessor->process($qty);

        if ($qty) {
            $item->setQty($qty);
        }
        $buyRequest = $this->productHelper->addParamsToBuyRequest(
            [],
            ['current_config' => $item->getBuyRequest()]
        );
        try {
            /** @var Collection $options */
            $options = $this->optionFactory->create()->getCollection()->addItemFilter([$itemId]);
            $item->setOptions($options->getOptionsByItem($itemId));
            $item->mergeBuyRequest($buyRequest);
            $quote->addProduct($item->getProduct(), $item->getBuyRequest());
            $this->quoteRepository->save($quote);
            if (null !== $customerId && $wishlist->isOwner($customerId)) {
                $item->delete();
            }
            $wishlist->save();
        } catch (ProductException $e) {
            throw new ProductException(__('This product(s) is out of stock.'));
        } catch (LocalizedException $e) {
            throw new LocalizedException(__('We can\'t add the item to Wish List right now.'));
        } catch (Exception $e) {
            throw new Exception(__('We can\'t add the item to Wish List right now.'));
        }

        return true;
    }

    /**
     * @param Wishlist $wishlist
     * @param null $customerId
     * @param $qtys
     * @return MessageBagInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function moveAllToCart(Wishlist $wishlist, $customerId, $qtys)
    {
        $isError = false;
        $messages = [];
        $addedProducts = [];
        $notSalable = [];
        $collection = $wishlist->getItemCollection()->setVisibilityFilter();
        /** @var Quote $quote */
        $quote = $this->quoteRepository->getActiveForCustomer($customerId);
        foreach ($collection as $item) {
            /** @var $item Item */
            try {
                $disableAddToCart = $item->getProduct()->getDisableAddToCart();
                $item->unsProduct();

                // Set qty
                $qty = 1;
                if (isset($qtys[$item->getId()])) {
                    $qty = $this->quantityProcessor->process($qtys[$item->getId()]);
                    if ($qty) {
                        $item->setQty($qty);
                    }
                }
                $item->getProduct()->setDisableAddToCart($disableAddToCart);
                // Add to cart
                if ($this->moveToCart($quote, $item->getId(), $qty, $customerId)) {
                    $addedProducts[] = $item->getProduct();
                }
            } catch (LocalizedException $e) {
                $isError = true;
                if ($e instanceof ProductException) {
                    $notSalable[] = $item;
                } else {
                    $messages[] = __('%1 for "%2".', trim($e->getMessage(), '.'), $item->getProduct()->getName());
                }
                $cartItem = $quote->getItemByProduct($item->getProduct());
                if ($cartItem) {
                    $quote->deleteItem($cartItem);
                    $this->quoteRepository->save($quote);
                }
            } catch (Exception $e) {
                $isError = true;
                $messages[] = __('We can\'t add this item to your shopping cart right now.');
            }
        }

        if ($notSalable) {
            $products = [];
            foreach ($notSalable as $item) {
                $products[] = '"' . $item->getProduct()->getName() . '"';
            }
            $messages[] = __(
                'We couldn\'t add the following product(s) to the shopping cart: %1.',
                join(', ', $products)
            );
        }

        if (true === $isError) {
            $response = [
                'error' => true,
                'messages' => $messages
            ];
        } else {
            $response['error'] = false;
            $response['messages'][] = __('Moved item(s) to cart successfully.');
        }
        if ($addedProducts) {
            $products = [];
            foreach ($addedProducts as $product) {
                /** @var $product Product */
                $products[] = '"' . $product->getName() . '"';
            }
            $response['messages'][] =  __('%1 product(s) have been added to shopping cart: %2.', count($addedProducts), join(', ', $products));

        }

        return new DataObject($response);
    }
}
