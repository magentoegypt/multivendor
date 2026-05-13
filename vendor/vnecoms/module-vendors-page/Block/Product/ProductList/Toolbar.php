<?php
namespace Vnecoms\VendorsPage\Block\Product\ProductList;

use Magento\Catalog\Helper\Product\ProductList;
use Magento\Catalog\Model\Product\ProductList\Toolbar as ToolbarModel;

class Toolbar extends \Magento\Catalog\Block\Product\ProductList\Toolbar
{
    /**
     * @var \Vnecoms\VendorsPage\Helper\Data
     */
    protected $_pageHelper;
    
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;
    
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Model\Session $catalogSession,
        \Magento\Catalog\Model\Config $catalogConfig,
        ToolbarModel $toolbarModel,
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        ProductList $productListHelper,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Vnecoms\VendorsPage\Helper\Data $pageHelper,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_pageHelper = $pageHelper;
        $this->_coreRegistry = $registry;
        parent::__construct(
            $context,
            $catalogSession,
            $catalogConfig,
            $toolbarModel,
            $urlEncoder,
            $productListHelper,
            $postDataHelper,
            $data
        );
    }

    /**
     * Get Vendor object
     *
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor()
    {
        return $this->_coreRegistry->registry('vendor');
    }
    
    /**
     * Return current URL with rewrites and additional parameters
     *
     * @param array $params Query parameters
     * @return string
     */
    public function getPagerUrl($params = [])
    {
        $urlParams = [];
        $urlParams['_escape'] = false;
        $urlParams['_query'] = $this->getRequest()->getQuery()->toArray();
        return $this->_pageHelper->getUrl($this->getVendor(),'items',$urlParams);
    }
}
