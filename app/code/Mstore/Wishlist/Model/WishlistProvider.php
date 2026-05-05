<?php declare(strict_types=1);

namespace Mstore\Wishlist\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Wishlist\Model\Wishlist;
use Magento\Wishlist\Model\WishlistFactory;

class WishlistProvider implements WishlistProviderInterface
{
    /**
     * @var Wishlist
     */
    protected $wishlist;

    /**
     * @var WishlistFactory
     */
    protected $wishlistFactory;

    /**
     * WishlistProvider constructor.
     * @param WishlistFactory $wishlistFactory
     */
    public function __construct(
        WishlistFactory $wishlistFactory
    ) {
        $this->wishlistFactory = $wishlistFactory;
    }

    /**
     * @inheritDoc
     */
    public function getWishlist($wishlistId, $customerId)
    {
        if ($this->wishlist) {
            return $this->wishlist;
        }
        /** @var Wishlist $wishlist */
        $wishlist = $this->wishlistFactory->create();
        if ($wishlistId) {
            $wishlist->load($wishlistId);
        } elseif ($customerId) {
            $wishlist->loadByCustomerId($customerId, true);
        }
        if (!$wishlist->getId() || $wishlist->getCustomerId() != $customerId) {
            throw new NoSuchEntityException(
                __('The requested Wish List doesn\'t exist.')
            );
        }
        $this->wishlist = $wishlist;
        return $wishlist;
    }
}
