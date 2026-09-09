<?php

declare(strict_types=1);

namespace HubMarket\DynamicMenu\Block;

use Magento\Catalog\Plugin\Block\Topmenu as CatalogTopmenu;
use Magento\Framework\Data\Tree\NodeFactory;
use Magento\Framework\Data\TreeFactory;
use Magento\Framework\View\Element\Template\Context;

/**
 * Uses Magento's native category menu renderer without the global MGS AMP
 * preference that is intended for AMP pages.
 */
class Topmenu extends \Magento\Theme\Block\Html\Topmenu
{
    private CatalogTopmenu $catalogTopmenu;

    private bool $categoryTreePrepared = false;

    public function __construct(
        Context $context,
        NodeFactory $nodeFactory,
        TreeFactory $treeFactory,
        CatalogTopmenu $catalogTopmenu,
        array $data = []
    ) {
        parent::__construct($context, $nodeFactory, $treeFactory, $data);
        $this->catalogTopmenu = $catalogTopmenu;
    }

    /**
     * Populate the tree through Magento_Catalog directly. The catalog plugin is
     * normally attached to Magento's Topmenu by DI, but MGS_Amp replaces that
     * class globally and prevents the native renderer from being used.
     */
    public function getHtml($outermostClass = '', $childrenWrapClass = '', $limit = 0)
    {
        if (!$this->categoryTreePrepared) {
            $this->catalogTopmenu->beforeGetHtml(
                $this,
                $outermostClass,
                $childrenWrapClass,
                $limit
            );
            $this->categoryTreePrepared = true;
        }

        return parent::getHtml($outermostClass, $childrenWrapClass, $limit);
    }

    public function getIdentities()
    {
        $this->catalogTopmenu->beforeGetIdentities($this);

        return parent::getIdentities();
    }

    /**
     * Absolute URL for a file under pub/media.
     *
     * The mega menu's promotional card carries one of the home page's own
     * pictures, and a template has no way to reach the media base URL on its
     * own — `getViewFileUrl()` resolves static view files, not uploads. This is
     * the block that renders that template, and it already holds the store
     * manager from `Magento\Framework\View\Element\Template`.
     *
     * No new constructor argument, deliberately: this store runs production
     * mode against a compiled DI config that already holds this class's
     * signature, and a compiled factory passes only the arguments it was
     * compiled for.
     */
    public function getMediaUrl(string $path): string
    {
        return $this->_storeManager->getStore()
                ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)
            . ltrim($path, '/');
    }
}
