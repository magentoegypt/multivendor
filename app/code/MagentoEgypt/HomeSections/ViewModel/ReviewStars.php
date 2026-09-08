<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HomeSections\ViewModel;

use Magento\Catalog\Block\Product\ReviewRendererInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Star rating markup for blocks that are not product listings.
 *
 * WHY THIS EXISTS AT ALL
 * ----------------------
 * `AbstractProduct::getReviewsSummaryHtml()` gives every catalogue listing block a
 * rating for free. Blocks that build their own rows from a collection — Bundle
 * Deals is the one today — extend `Template` instead and inherit nothing, so
 * their cards had no stars. QA cycle 1 filed exactly that against the bundle
 * rail ("restore the missing card content ... Star Ratings/Reviews layout").
 *
 * WHY A VIEW MODEL RATHER THAN A CONSTRUCTOR ARGUMENT
 * ---------------------------------------------------
 * Adding a constructor parameter to an existing block does not take effect in
 * production until `setup:di:compile` runs, and that wipes `generated/` — a
 * multi-minute 500 across the whole storefront. A NEW class has no compiled
 * definition to be stale, so the object manager resolves it by reflection and it
 * works immediately. Layout `xsi:type="object"` also refuses anything that is not
 * an ArgumentInterface, which rules out passing the renderer block directly.
 *
 * This is the same route `VendorNames` already takes into the product rails.
 *
 * @see \MagentoEgypt\HomeSections\Block\BundleDeals::getReviewsSummaryHtml()
 */
class ReviewStars implements ArgumentInterface
{
    public function __construct(
        private readonly ReviewRendererInterface $reviewRenderer
    ) {
    }

    /**
     * The compact rating block, as the product cards render it.
     *
     * $displayIfNoReviews is TRUE deliberately. Core returns an empty string
     * before it reaches any template when a product has no rating summary:
     *
     *     if (null === $product->getRatingSummary() && !$displayIfNoReviews) {
     *         return '';
     *     }
     *
     * Passing true is what lets this theme's summary_short.phtml override draw
     * its empty five-star track, which is what keeps every card in a row the same
     * height. 94% of this catalogue carries no review, so without it the rows
     * render ragged — the defect QA reported as "star ratings missing".
     */
    public function forProduct(Product $product): string
    {
        return (string) $this->reviewRenderer->getReviewsSummaryHtml(
            $product,
            ReviewRendererInterface::SHORT_VIEW,
            true
        );
    }
}
