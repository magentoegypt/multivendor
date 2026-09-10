<?php
declare(strict_types=1);

namespace MagentoEgypt\CheckoutExtend\Model\Pdf\Doc;

/**
 * The Creditmemo PDF, drawing on a page that shapes Arabic (CL036-DEV01.23).
 *
 * Core's newPage() is reproduced rather than extended because the one line
 * worth changing is the page's construction, and that line is in the middle of
 * it. Everything else — the y reset, appending to the document, the table
 * header — is core's, unchanged.
 *
 * Keep in sync on upgrade — diff against
 * vendor/magento/module-sales/Model/Order/Pdf/Creditmemo.php
 */
class Creditmemo extends \Magento\Sales\Model\Order\Pdf\Creditmemo
{
    use ArabicPageTrait;

    /**
     * @param array<string, mixed> $settings
     * @return \Zend_Pdf_Page
     */
    public function newPage(array $settings = [])
    {
        $page = $this->hmArabicPage($settings);
        $this->_getPdf()->pages[] = $page;
        $this->y = 800;

        if (!empty($settings['table_header'])) {
            $this->_drawHeader($page);
        }

        return $page;
    }
}
