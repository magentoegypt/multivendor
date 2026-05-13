<?php

namespace Vnecoms\VendorsCms\Controller\Vendors\Page;

use Magento\Framework\Filter\FilterInput;

class PostDataProcessor
{
    /**
     * Authorization level of a basic admin session.
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsCms::page';
    /**
     * @var \Magento\Framework\Stdlib\DateTime\Filter\Date
     */
    protected $dateFilter;

    /**
     * @var \Magento\Framework\View\Model\Layout\Update\ValidatorFactory
     */
    protected $validatorFactory;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    protected $vendorSession;

    /**
     * @param \Magento\Framework\Stdlib\DateTime\Filter\Date               $dateFilter
     * @param \Magento\Framework\Message\ManagerInterface                  $messageManager
     * @param \Magento\Framework\View\Model\Layout\Update\ValidatorFactory $validatorFactory
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\Filter\Date $dateFilter,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\View\Model\Layout\Update\ValidatorFactory $validatorFactory
    ) {
        $this->dateFilter = $dateFilter;
        $this->messageManager = $messageManager;
        $this->validatorFactory = $validatorFactory;
    }

    /**
     * Filtering posted data. Converting localized data if needed.
     *
     * @param array $data
     *
     * @return array
     */
    public function filter($data)
    {
        $filterRules = [];

        foreach (['custom_theme_from', 'custom_theme_to'] as $dateField) {
            if (!empty($data[$dateField])) {
                $filterRules[$dateField] = $this->dateFilter;
            }
        }

        return (new FilterInput($filterRules, [], $data))->getUnescaped();
    }

    /**
     * Validate post data.
     *
     * @param array $data
     *
     * @return bool Return FALSE if someone item is invalid
     */
    public function validate($data)
    {
        $errorNo = true;
        if (!empty($data['layout_update_xml']) || !empty($data['custom_layout_update_xml'])) {
            /** @var $validatorCustomLayout \Magento\Framework\View\Model\Layout\Update\Validator */
            $validatorCustomLayout = $this->validatorFactory->create();
            if (!empty($data['layout_update_xml']) && !$validatorCustomLayout->isValid($data['layout_update_xml'])) {
                $errorNo = false;
            }
            if (!empty($data['custom_layout_update_xml'])
                && !$validatorCustomLayout->isValid($data['custom_layout_update_xml'])
            ) {
                $errorNo = false;
            }
            foreach ($validatorCustomLayout->getMessages() as $message) {
                $this->messageManager->addError($message);
            }
        }

        return $errorNo;
    }

    /**
     * Check if required fields is not empty.
     *
     * @param array $data
     *
     * @return bool
     */
    public function validateRequireEntry(array $data)
    {
        $requiredFields = [
            'title' => __('Page Title'),
            'is_active' => __('Status'),
            'page_layout' => __('Page Layout'),
        ];
        $errorNo = true;
        foreach ($data as $field => $value) {
            if (in_array($field, array_keys($requiredFields)) && $value == '') {
                $errorNo = false;
                $this->messageManager->addError(
                    __('To apply changes you should fill in hidden required "%1" field', $requiredFields[$field])
                );
            }
        }

        return $errorNo;
    }

    public function addVendorIdData(array $data)
    {
        $data['vendor_id'] = $this->getVendor()->getId();

        return $data;
    }

    /**
     * @return \Vnecoms\Vendors\Model\Session
     */
    public function getVendor()
    {
        return ($this->vendorSession != null) ? $this->vendorSession->getVendor() :
            \Magento\Framework\App\ObjectManager::getInstance()->get('Vnecoms\Vendors\Model\Session')->getVendor();
    }
}
