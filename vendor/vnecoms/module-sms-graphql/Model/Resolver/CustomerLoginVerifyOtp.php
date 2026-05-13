<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\SmsGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Vnecoms\SmsGraphQl\Model\Sms\VerifyOtp;
use Magento\Framework\Exception\InvalidEmailOrPasswordException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

/**
 * Products field resolver, used for GraphQL request processing.
 */
class CustomerLoginVerifyOtp implements ResolverInterface
{
    /**
     * @var VerifyOtp
     */
    private $model;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * CustomerLoginVerifyOtp constructor.
     * @param VerifyOtp $sendOtp
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        VerifyOtp $sendOtp,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->model = $sendOtp;
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $currentUserId = $context->getUserId();
        $mobileNum = $args["input"]["mobile"];
        $otp = $args["input"]["otp"];

        /*
        if (!$args["input"]['secure_key']) {
            throw new GraphQlInputException(__(__("The Secure Key cant be null")));
        } */

        $result = $this->model->execute($mobileNum, $otp , $currentUserId);

        /*
        $secureKey = $args["input"]['secure_key'];
        $email = $this->customerSession->getData($secureKey);
        if(!$email) throw new LocalizedException(__("Can't retrieve login information."));
        try {
            $customer = $this->customerRepository->get($email);
            $this->customerSession->setCustomerDataAsLoggedIn($customer);

            $result['email'] = $email;
        } catch (NoSuchEntityException $e) {
            throw new InvalidEmailOrPasswordException(__('Invalid login or password.'));
        }  */

        return $result;
    }
}
