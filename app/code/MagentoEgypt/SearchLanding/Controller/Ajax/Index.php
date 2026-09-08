<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\SearchLanding\Controller\Ajax;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\View\LayoutFactory;
use MagentoEgypt\SearchLanding\Block\Ajax\Results;
use Psr\Log\LoggerInterface;

/**
 * GET /search/ajax/?q=… — live-search results for the /search hero field.
 *
 * RETURNS HTML, NOT JSON, deliberately. The product panel reuses the
 * storefront's own card markup, which is produced by block helpers (price box,
 * rating summary, image). Serialising that to JSON would mean rebuilding the
 * card in JavaScript and maintaining two versions of it — the PLP's and the
 * live panel's — which drift the first time pricing changes. Rendering here
 * means one card definition, already translated and already store-scoped.
 *
 * Marked no-store: results depend on the query, the store view and catalog
 * visibility, and are cheap to regenerate. Letting a proxy or the browser cache
 * them risks showing one shopper another shopper's store view.
 */
class Index implements HttpGetActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly RawFactory $rawFactory,
        private readonly LayoutFactory $layoutFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(): Raw
    {
        $result = $this->rawFactory->create();
        $result->setHeader('Content-Type', 'text/html; charset=UTF-8', true);
        $result->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate', true);
        $result->setHeader('X-Robots-Tag', 'noindex', true);

        $query = trim((string) $this->request->getParam('q', ''));

        /*
         * The length floor is enforced on the SERVER as well as in the JS. The
         * endpoint is a public URL: without this, anything could hit it with
         * q=a and make the search engine score every product in the catalog, on
         * a host whose FPM pool allows five concurrent children.
         */
        if (mb_strlen($query) < Results::MIN_QUERY_LENGTH) {
            return $result->setContents('');
        }

        try {
            /** @var Results $block */
            $block = $this->layoutFactory->create()->createBlock(Results::class);
            $block->setData('query_text', $query);
            $block->setTemplate('MagentoEgypt_SearchLanding::ajax/results.phtml');

            return $result->setContents($block->toHtml());
        } catch (\Throwable $e) {
            /*
             * An empty body makes the JS leave the last good panel in place
             * rather than painting an error state over working results. The
             * failure is logged, not shown.
             */
            $this->logger->error('SearchLanding ajax: ' . $e->getMessage());

            return $result->setContents('');
        }
    }
}
