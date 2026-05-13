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
use Vnecoms\Sms\Model\Mobile;

/**
 * Get vendor product
 */
class SendOtp
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
     * SendOtp constructor.
     * @param Builder $builder
     * @param ExtensibleDataObjectConverter $dataObjectConverter
     * @param \Vnecoms\Sms\Helper\Data $helper
     * @param \Vnecoms\Sms\Model\MobileFactory $mobileFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Vnecoms\Sms\Model\Otp\Generator $generator
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Email\Model\Template\Filter $filter
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
        \Magento\Email\Model\Template\Filter $filter
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
    }

    /**
     * @param $args
     * @param ResolveInfo $info
     * @param ContextInterface $context
     * @return mixed
     */
    public function execute($args, ContextInterface $context, $isCheckUnique = false)
    {
        $currentUserId = $context->getUserId();

        $mobileNum = $args["input"]["mobile"];
        $isResend = $args["input"]["resend"];
        $secure_key = null;

        try{
            $customer = $this->customerFactory->create()->load($currentUserId);
            /* Save the mobile number*/
            $mobile = $this->mobileFactory->create();

            if($this->helper->isUniqueMobileNumber() && $isCheckUnique){
                /* Check if the mobile is used by other */
                $collection = $this->customerFactory->create()->getCollection()
                    ->addAttributeToFilter('mobilenumber', $mobileNum)
                    ->addAttributeToFilter('entity_id', ['neq' => $customer->getId()]);
                if($collection->count()){
                    throw new LocalizedException(__("The mobile number is used by another customer account."));
                }
            }

            if($customer->getId()){
                if($mobileNum == $this->customerSession->getCustomer()->getMobilenumber()
                    && $isCheckUnique
                ){
                    throw new LocalizedException(__("You are using this mobile number already."));
                }

                $mobile->load($customer->getId(), 'customer_id');
            }else{
                $mobileCollection = $mobile->getCollection()
                    ->addFieldToFilter('mobile', $mobileNum)
                    ->addFieldToFilter('status', Mobile::STATUS_NOT_VERIFIED)
                    ->addFieldToFilter('customer_id', ['null' => true]);
                if($mobileCollection->count()){
                    $mobile = $mobileCollection->getFirstItem();
                }
            }
            
            $currentResendCount = (int) $this->customerSession->getOtpResendCount();
            /* Block customer if he is sending OTP too much times.*/
            if($currentResendCount > ($this->helper->getOtpMaxResendingTimes()-1)){
                $lastTimeResend = $this->customerSession->getLastTimeResendOtp();
                $blockTime = $this->helper->getOtpResendBlockTime();
                if(($lastTimeResend + $blockTime) < $this->date->timestamp()){
                    $this->customerSession->setOtpResendCount(0);
                    $currentResendCount = 0;
                }else{
                    throw new \Exception(__("You are sending OTP too much times."));
                }
            }

            if($isResend){
                $currentResendCount++;
                $this->customerSession->setLastTimeResendOtp($this->date->timestamp());
                $this->customerSession->setOtpResendCount($currentResendCount);
            }

            if(
                $mobile->getMobileId() &&
                !$mobile->isExpiredOTP()
            ) {
                $otp = $mobile->getOtp();
            }else{
                $otp = $this->generator->generateCode();
            }
            $mobile->addData([
                'customer_id' => $customer->getId() ? $customer->getId() : null,
                'mobile' => $mobileNum,
                'additional_data' => $secure_key,
                'otp' => $otp,
                'otp_created_at' => $this->date->timestamp(),
                'status' => 0,
            ])->save();

            /* Send otp Message*/
            $message = $this->helper->getOtpMessage();
            $this->filter->setVariables(['otp_code' => $otp]);
            $message = $this->filter->filter($message);
            $this->helper->sendSms($mobileNum, $message);

            $data = [
                'success' => true,
                'resend' => $this->customerSession->getData('otp_resend_count'),
                'msg' => null,
            ];
        }catch(\Exception $e){
            $data = [
                'success' => false,
                'resend' => $this->customerSession->getData('otp_resend_count'),
                'msg' => $e->getMessage(),
            ];
        }

        return $data;
    }
}
