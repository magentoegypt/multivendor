<?php
namespace MGS\AjaxCart\Block\Product;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\ObjectManager;

/**
 * Class Config
 * @package MGS\AjaxCart\Block\Product
 */
class Config extends \Magento\Framework\View\Element\Template
{
    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $jsonHelper;

    /**
     * @param Registry $coreRegistry
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Registry $coreRegistry,
        Context $context,
        array $data = []
    ) {
        $this->jsonHelper = ObjectManager::getInstance()->get(\Magento\Framework\Serialize\Serializer\Json::class);
        $this->coreRegistry = $coreRegistry;

        parent::__construct($context, $data);
    }

    /**
     * Get additional JSON-formatted ACP options for product page
     *
     * @return string
     */
    public function getOptions()
    {
        return $this->jsonHelper->serialize([
            'productCategoryUrl' => $this->getCategoryUrl()
        ]);
    }

    /**
     * Get first of the product categories
     *
     * @return string|null
     */
    private function getCategoryUrl()
    {
        if (!$product = $this->coreRegistry->registry('current_product')) {
            return null;
        }
        $firstCategory = $product->getCategoryCollection()->getFirstItem();
        return  $firstCategory->getUrl();
    }
}
