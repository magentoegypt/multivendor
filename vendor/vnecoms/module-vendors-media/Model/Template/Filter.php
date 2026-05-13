<?php
namespace Vnecoms\VendorsMedia\Model\Template;

class Filter implements \Laminas\Filter\FilterInterface
{
    const CONSTRUCTION_IMAGE_PATTERN = '/{{image\s*url\s*=\s*"(.*?)"}}/si';

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ){
        $this->storeManager = $storeManager;
    }

    /**
     * @param string $value
     * @throws Exception
     */
    public function filter($value){
        if (
            preg_match_all(self::CONSTRUCTION_IMAGE_PATTERN, $value, $constructions, PREG_SET_ORDER)
        ) {
            foreach ($constructions as $construction) {
                try {
                    $replacedValue = $this->imageDirective($construction);
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
    public function imageDirective($construction){
        $url = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $url.= \Vnecoms\VendorsMedia\Helper\Data::MEDIA_FOLDER.'/'. ltrim($construction[1], '/');

        return $url;
    }
}
