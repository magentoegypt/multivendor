<?php
namespace Vnecoms\SmsGraphQl\Plugin\Customer;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Store\Api\Data\StoreInterface;

class CreateCustomerAccount
{
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Vnecoms\Sms\Helper\Data
     */
    protected $helper;

    /**
     * CreateCustomerAccount constructor.
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Vnecoms\Sms\Helper\Data $helper
     */
    public function __construct(
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Vnecoms\Sms\Helper\Data $helper
    ) {
        $this->customerFactory = $customerFactory;
        $this->helper = $helper;
    }

    /**
     * @param \Magento\Customer\Block\Account\AuthenticationPopup $subject
     * @param \Closure $proceed
     * @return mixed
     */
    public function beforeExecute(
        \Magento\CustomerGraphQl\Model\Customer\CreateCustomerAccount $subject,
        array $data,
        StoreInterface $store
    ) {
        if (empty($data['mobilenumber'])) {
            throw new GraphQlInputException(__('Required parameters are missing: Mobile Number'));
        }

        if($this->helper->isUniqueMobileNumber()){
            /* Check if the mobile is used by other */
            $collection = $this->customerFactory->create()->getCollection()
                ->addAttributeToFilter('mobilenumber', $data['mobilenumber']);

            if($collection->count()){
                throw new GraphQlInputException(__("The mobile number is used by another customer account."));
            }
        }

    }

    /**
     * @param \Magento\Customer\Block\Account\AuthenticationPopup $subject
     * @param \Closure $proceed
     * @return mixed
     */
    public function afterExecute(
        \Magento\CustomerGraphQl\Model\Customer\CreateCustomerAccount $subject,
        $result,
        array $data,
        StoreInterface $store
    ) {
        $customer = $result;
        $customer = $this->customerFactory->create()->load($customer->getId());
        $customer->setData('mobilenumber', $data["mobilenumber"])->save();
        return $result;
    }
}
