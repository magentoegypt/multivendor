<?php
namespace Vnecoms\VendorsCustomTheme\Model\Template;

class Filter implements \Laminas\Filter\FilterInterface
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\App\ProductMetadata
     */
    protected $productMetadata;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ProductMetadata $productMetadata
    ){
        $this->storeManager = $storeManager;
        $this->productMetadata = $productMetadata;
    }

    /**
     * @param string $value
     * @throws Exception
     */
    public function filter($value)
    {
        return $value;
    }

    /**
     * Get Conditions
     * @return string
     */
    public function getConditions()
    {
        $version = $this->productMetadata->getVersion();
        $conditions = ['1' => [
            'type' => 'Magento\CatalogWidget\Model\Rule\Condition\Combine',
            'aggregator' => 'all',
            'value' => 1,
            'new_child' => '',
        ]];
        return (version_compare($version, '2.2.0', '<'))?serialize($conditions):json_encode($conditions);
    }

    /**
     * @param string $string
     * @param \Magento\Framework\DataObject $block
     */
    public function processParams($string, \Magento\Framework\DataObject $block)
    {
        preg_match_all('/\s*(.*?)\s*=\s*"(.*?)"/si', $string, $constructions, PREG_SET_ORDER);
        foreach($constructions as $con){
            if(trim($con[1]) == 'template'){
                $block->setTemplate(trim($con[2]));
                continue;
            }
            $block->setData(trim($con[1]), trim($con[2]));
        }
    }
}
