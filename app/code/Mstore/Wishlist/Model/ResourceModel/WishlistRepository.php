<?php declare(strict_types=1);

namespace Mstore\Wishlist\Model\ResourceModel;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Product as ProductHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Wishlist\Model\Item;
use Magento\Wishlist\Model\ItemFactory;
use Magento\Wishlist\Model\LocaleQuantityProcessor;
use Magento\Wishlist\Model\ResourceModel\Wishlist as WishlistResourceModel;
use Mstore\Wishlist\Api\WishlistRepositoryInterface;
use Mstore\Wishlist\Model\Wishlist;
use Mstore\Wishlist\Model\WishlistFactory;
use Mstore\Wishlist\Model\WishlistProviderInterface;

class WishlistRepository implements WishlistRepositoryInterface
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
     * @var ProductRepositoryInterface
     */
    private $productRepository;

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
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * WishlistRepository constructor.
     * @param WishlistFactory $wishlistFactory
     * @param ItemFactory $itemFactory
     * @param CartRepositoryInterface $quoteRepository
     * @param ProductHelper $productHelper
     * @param WishlistProviderInterface $wishlistProvider
     * @param WishlistResourceModel $wishlistResource
     * @param LocaleQuantityProcessor $quantityProcessor
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        WishlistFactory $wishlistFactory,
        ItemFactory $itemFactory,
        CartRepositoryInterface $quoteRepository,
        ProductHelper $productHelper,
        WishlistProviderInterface $wishlistProvider,
        WishlistResourceModel $wishlistResource,
        LocaleQuantityProcessor $quantityProcessor,
        ProductRepositoryInterface $productRepository
    ) {
        $this->wishlistFactory = $wishlistFactory;
        $this->productRepository = $productRepository;
        $this->quoteRepository = $quoteRepository;
        $this->productHelper = $productHelper;
        $this->wishlistResource = $wishlistResource;
        $this->wishlistProvider = $wishlistProvider;
        $this->quantityProcessor = $quantityProcessor;
        $this->itemFactory = $itemFactory;
    }

    /**
     * @inheritDoc
     */
    public function getWishlist($customerId)
    {
        /** @var Wishlist $wishlist */
        $wishlist = $this->wishlistFactory->create();
        $this->wishlistResource->load($wishlist, $customerId, 'customer_id');

        if (null === $wishlist->getId()) {
            throw new LocalizedException(__('The wishlist item does not exist.'));
        }

        return $wishlist;
    }

    /**
     * @inheritDoc
     */
    public function addItem($customerId, $productId, $buyRequest): bool
    {
        /** @var Wishlist $wishlist */
        $wishlist = $this->wishlistFactory->create();
        $wishlist->loadByCustomerId($customerId, true);
        try {
            $product = $this->productRepository->getById($productId);
        } catch (NoSuchEntityException $e) {
            throw new NoSuchEntityException(__('The product does not exist.'));
        }

        $wishlist->addNewItem($product, $buyRequest->getData());
        if ($wishlist->isObjectNew()) {
            $wishlist->save();
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function removeItem($customerId, $itemId): bool
    {
        /** @var Item $item */
        $item = $this->itemFactory->create();
        $item->load($itemId);
        if (!$item->getId()) {
            throw new LocalizedException(__('The wishlist item does not exist.'));
        }
        /** @var Wishlist $wishlist */
        $wishlist = $this->wishlistFactory->create();
        $wishlist->loadByCustomerId($customerId, true);

        try {
            $item->delete();
            $wishlist->save();
        } catch (LocalizedException $e) {
            throw new LocalizedException(__('We can\'t delete the item from Wish List right now because of an error: %1.', $e->getMessage()));
        } catch (Exception $e) {
            throw new Exception(__('We can\'t delete the item from the Wish List right now.'));
        }

        return true;
    }
}
