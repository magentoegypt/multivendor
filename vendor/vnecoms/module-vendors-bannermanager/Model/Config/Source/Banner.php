<?php
/**
 * Created by PhpStorm.
 * User: mrtuvn
 * Date: 12/04/2017
 * Time: 12:39
 */

namespace Vnecoms\BannerManager\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Convert\DataObject;

class Banner implements OptionSourceInterface
{
    /** @var \Vnecoms\BannerManager\Model\BannerFactory  */
    protected $bannerFactory;
    /** @var SearchCriteriaBuilder  */
    protected $searchCriteriaBuilder;
    /** @var BannerRepositoryInterface  */
    protected $bannerRepository;
    /**
     * @var DataObject
     */
    private $objectConverter;

    /**
     * Banner constructor.
     * @param \Vnecoms\BannerManager\Api\BannerRepositoryInterface $bannerRepository
     * @param \Vnecoms\BannerManager\Model\BannerFactory $bannerFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param DataObject $objectConverter
     */
    public function __construct(
        \Vnecoms\BannerManager\Api\BannerRepositoryInterface $bannerRepository,
        \Vnecoms\BannerManager\Model\BannerFactory $bannerFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        DataObject $objectConverter
    ) {
        $this->bannerRepository = $bannerRepository;
        $this->bannerFactory = $bannerFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->objectConverter = $objectConverter;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $banners = $this->bannerRepository->getList($this->searchCriteriaBuilder->create())->getItems();
        $bannersArray = $this->objectConverter->toOptionArray($banners, 'banner_id', 'title');
        //var_dump($bannersArray);die;
        $optionArray[]= ['value' => '','label' => '--- Select Banner ---'];
        foreach ($bannersArray as $option) {
            $optionArray[] = [
                'value' => $option['value'],
                'label' => $option['label']
            ];
        }
        return $optionArray;
    }
}
