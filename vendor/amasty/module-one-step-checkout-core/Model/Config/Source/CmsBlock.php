<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Convert\DataObject;

class CmsBlock implements OptionSourceInterface
{
    /**
     * @var BlockRepositoryInterface
     */
    private $blockRepository;

    /**
     * @var SearchCriteriaInterface
     */
    private $searchCriteria;

    /**
     * @var DataObject
     */
    private $objectConverter;

    public function __construct(
        BlockRepositoryInterface $blockRepository,
        SearchCriteriaInterface $searchCriteria,
        DataObject $objectConverter
    ) {
        $this->blockRepository = $blockRepository;
        $this->searchCriteria = $searchCriteria;
        $this->objectConverter = $objectConverter;
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        $result = ['value' => 0, 'label' => __('Please, select a static block')];
        $items = $this->blockRepository->getList($this->searchCriteria)->getItems();

        $options = $this->prepareOptions($items);
        if (empty($options) || array_shift($options) === null) {
            $options = $this->objectConverter->toOptionArray(
                $items,
                'block_id',
                'title'
            );
        }

        array_unshift($options, $result);

        return $options;
    }

    /**
     * The method inits options for old version of magento
     *
     * @param array $items
     *
     * @return array
     */
    private function prepareOptions($items = [])
    {
        $options = array_map(function ($item) {
            if (is_array($item)) {
                $value = $item['identifier'] ?: '';
                $label = $item['title'] ?: '';
                if ($value && $label) {
                    return ['value' => $value, 'label' => $label];
                }
            }
        }, $items);

        return $options;
    }
}
