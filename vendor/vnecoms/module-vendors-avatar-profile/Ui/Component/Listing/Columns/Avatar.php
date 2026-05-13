<?php
namespace Vnecoms\VendorsAvatarProfile\Ui\Component\Listing\Columns;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

class Avatar extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * @var \Vnecoms\VendorsAvatarProfile\Helper\Data
     */
    protected $helperData;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;

    /**
     * Avatar constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Vnecoms\VendorsAvatarProfile\Helper\Data $helperData
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Vnecoms\VendorsAvatarProfile\Helper\Data $helperData,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->_urlBuilder = $urlBuilder;
        $this->helperData = $helperData;
        $this->_customerFactory = $customerFactory;
    }
    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as & $item) {
                $customer = $this->_customerFactory->create()->load($item['entity_id']);
                $picture_url = $this->helperData->getAvatarOfCustomer($customer->getProfilePicture());
                $item[$fieldName . '_src'] = $picture_url;
                $item[$fieldName . '_orig_src'] = $picture_url;
                $item[$fieldName . '_alt'] = 'The profile picture';
            }
        }
        return $dataSource;
    }
}
