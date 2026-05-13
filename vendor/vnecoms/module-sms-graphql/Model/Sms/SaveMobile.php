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
class SaveMobile
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
     * SaveMobile constructor.
     * @param Builder $builder
     * @param ExtensibleDataObjectConverter $dataObjectConverter
     * @param \Vnecoms\Sms\Helper\Data $helper
     * @param \Vnecoms\Sms\Model\MobileFactory $mobileFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     */
    public function __construct(
        Builder $builder,
        ExtensibleDataObjectConverter $dataObjectConverter,
        \Vnecoms\Sms\Helper\Data $helper,
        \Vnecoms\Sms\Model\MobileFactory $mobileFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory
    ) {
        $this->builder = $builder;
        $this->dataObjectConverter = $dataObjectConverter;
        $this->helper = $helper;
        $this->mobileFactory = $mobileFactory;
        $this->customerFactory = $customerFactory;
    }

    /**
     * @param $args
     * @param ResolveInfo $info
     * @param ContextInterface $context
     * @return mixed
     */
    public function execute($args, ContextInterface $context)
    {
        $currentUserId = $context->getUserId();

        $mobileNum = $args["input"]["mobile"];
        $otp = $args["input"]["otp"];
        try{
            if(!$mobileNum){
                throw new LocalizedException(__("Please enter your mobile number."));
            }

            $customer = $this->customerFactory->create()->load($currentUserId);
            if(
                $customer->getMobilenumber() != $mobileNum &&
                $this->helper->isEnableVerifyingCustomerMobile()
            ) {
                $mobile = $this->mobileFactory->create();
                $collection = $mobile->getCollection()
                    ->addFieldToFilter('mobile', $mobileNum)
                    ->addFieldToFilter('otp', $otp);
                if(!$collection->count()) throw new LocalizedException(__("The otp is not valid."));
            }

            $customer->setData('mobilenumber', $mobileNum)->save();

            /* Delete all otp rows relate to mobule num*/
            $collection = $this->mobileFactory->create()->getCollection()
                ->addFieldToFilter('mobile', $mobileNum);
            foreach($collection as $mobile){
                $mobile->delete();
            }
        }catch(\Exception $e){
            throw new GraphQlInputException(__($e->getMessage()));
        }
        return ["result" => true];
    }
}
