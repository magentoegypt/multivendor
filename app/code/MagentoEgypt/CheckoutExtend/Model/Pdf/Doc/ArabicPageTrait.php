<?php
declare(strict_types=1);

namespace MagentoEgypt\CheckoutExtend\Model\Pdf\Doc;

use MagentoEgypt\CheckoutExtend\Model\Pdf\ArabicPage;

/**
 * The one behaviour the three sales documents share: build pages that shape
 * Arabic. Core's own newPage() differs slightly per document (the credit memo
 * draws its header twice over, the others once), so each subclass keeps its own
 * newPage and calls in here for the page itself.
 */
trait ArabicPageTrait
{
    /**
     * @param array<string, mixed> $settings
     */
    private function hmArabicPage(array $settings): ArabicPage
    {
        $pageSize = !empty($settings['page_size']) ? $settings['page_size'] : \Zend_Pdf_Page::SIZE_A4;

        return new ArabicPage($pageSize);
    }
}
