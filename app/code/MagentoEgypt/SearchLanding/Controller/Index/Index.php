<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\SearchLanding\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * GET /search — the search landing page.
 *
 * HttpGetActionInterface only. There is nothing to post here: the hero field
 * submits to catalogsearch/result like every other search entry point on the
 * site, so this action never needs to accept a form.
 *
 * Everything the page renders comes from the view model wired in
 * view/frontend/layout/search_index_index.xml; the controller's whole job is to
 * hand back a page result.
 */
class Index implements HttpGetActionInterface
{
    public function __construct(
        private readonly PageFactory $pageFactory
    ) {
    }

    public function execute(): Page
    {
        return $this->pageFactory->create();
    }
}
