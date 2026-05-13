<?php
namespace Vnecoms\VendorsCustomTheme\Model\Template\Filter;

use Magento\Framework\App\ObjectManager;

class NewProducts extends \Vnecoms\VendorsCustomTheme\Model\Template\Filter
{
    const CONSTRUCTION_NEW_PRODUCTS_PATTERN = '/{{newproducts\s*(.*?)}}/si';

    /**
     * @param string $value
     * @throws Exception
     */
    public function filter($value)
    {
        if (
            preg_match_all(self::CONSTRUCTION_NEW_PRODUCTS_PATTERN, $value, $constructions, PREG_SET_ORDER)
        ) {
            foreach ($constructions as $construction) {
                try {
                    $replacedValue = $this->newProductsDirective($construction);
                } catch (\Exception $e) {
                    throw $e;
                }
                $value = str_replace($construction[0], $replacedValue, $value);
            }
        }
        
        return $value;
    }
    
    /**
     * Process image url
     * 
     * @param array $construction
     */
    public function newProductsDirective($construction)
    {
        $om = ObjectManager::getInstance();
        $layout = $om->create('Magento\Framework\View\LayoutInterface');
        $block = $layout->createBlock('Vnecoms\VendorsCustomTheme\Block\Catalog\NewProducts', 'newproducts_'.rand(100,999));
        $this->processParams($construction[1], $block);
        
        $block->setData(
            'conditions_encoded',
            $this->getConditions()
        );
        
        return $block->toHtml();
    }
}
