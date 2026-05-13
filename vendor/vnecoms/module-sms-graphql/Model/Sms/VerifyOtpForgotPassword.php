<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\SmsGraphQl\Model\Sms;

use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Argument\SearchCriteria\Builder;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

/**
 * Get vendor product
 */
class VerifyOtpForgotPassword
{
    /**
     * @var \Vnecoms\Sms\Helper\Data
     */
    protected $helper;

    /**
     * @var \Vnecoms\Sms\Model\MobileFactory
     */
    protected $mobileFactory;


    /**
     * @var Builder
     */
    private $builder;

    /**
     * @var ExtensibleDataObjectConverter
     */
    private $dataObjectConverter;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Vnecoms\Sms\Model\Otp\Generator
     */
    protected $generator;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;

    /**
     * @var \Magento\Email\Model\Template\Filter
     */
    protected $filter;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Magento\Customer\Model\AccountManagement
     */
    protected $accountmanagement;

    /**
     * VerifyOtpForgotPassword constructor.
     * @param Builder $builder
     * @param ExtensibleDataObjectConverter $dataObjectConverter
     * @param \Vnecoms\Sms\Helper\Data $helper
     * @param \Vnecoms\Sms\Model\MobileFactory $mobileFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Vnecoms\Sms\Model\Otp\Generator $generator
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Email\Model\Template\Filter $filter
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Customer\Model\AccountManagement $accountManagement
     */
    public function __construct(
        Builder $builder,
        ExtensibleDataObjectConverter $dataObjectConverter,
        \Vnecoms\Sms\Helper\Data $helper,
        \Vnecoms\Sms\Model\MobileFactory $mobileFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Vnecoms\Sms\Model\Otp\Generator $generator,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Email\Model\Template\Filter $filter,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Model\AccountManagement $accountManagement
    ) {
        $this->date = $dateTime;
        $this->filter  = $filter;
        $this->builder = $builder;
        $this->dataObjectConverter = $dataObjectConverter;
        $this->helper = $helper;
        $this->mobileFactory = $mobileFactory;
        $this->customerFactory = $customerFactory;
        $this->generator = $generator;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->accountManagement = $accountManagement;
    }

    /**
     * @param $mobileNum
     * @param $otp
     * @param $customerId
     * @param bool $isUnique
     * @return array
     */
    public function execute($mobileNum, $otp , $customerId, $isUnique = false)
    {
        try{
            if($this->helper->isUniqueMobileNumber() && $isUnique){
                /* Check if the mobile is used by other */
                $collection = $this->customerFactory->create()->getCollection()
                    ->addAttributeToFilter('mobilenumber', $mobileNum)
                    ->addAttributeToFilter('entity_id', ['neq' => $customerId]);

                if($collection->count()){
                    throw new \Magento\Framework\Exception\LocalizedException(__("The mobile number is used by another customer account."));
                }
            }

            /* Save the mobile number*/
            $collection = $this->mobileFactory->create()->getCollection()
                ->addFieldToFilter('mobile', $mobileNum)
                ->addFieldToFilter('otp', $otp);

            if(!$collection->count()){
                throw new \Magento\Framework\Exception\LocalizedException(__("The OTP code is not valid."));
            }

            $mobile = $collection->getFirstItem();

            $customerId = $mobile->getData('customer_id');

            if((strtotime($mobile->getOtpCreatedAt()) + $this->helper->getOtpExpiredPeriodTime()) < $this->date->timestamp()){
                throw new \Magento\Framework\Exception\LocalizedException(__("The OTP code is expired."));
            }

            $customer = $this->customerRepository->getById($customerId);
            /** @var \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository */
            $helper = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\User\Helper\Data');
            $newResetPasswordLinkToken = $helper->generateResetPasswordLinkToken();
            $this->accountManagement->changeResetPasswordLinkToken($customer, $newResetPasswordLinkToken);

            $mobile->delete();
            $this->customerSession->setOtpResendCount(0);
            $data = [
                'success' => true,
                'customer_id' => $customerId,
                'otp' => $otp,
                'resetPasswordToken' => $newResetPasswordLinkToken,
                'email' => $customer->getEmail(),
            ];
        }catch(\Exception $e){
            $data = [
                'success' => false,
                'msg' => $e->getMessage(),
            ];
        }

        return $data;
    }
}
