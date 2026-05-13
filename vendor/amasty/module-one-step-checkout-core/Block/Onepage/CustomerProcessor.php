<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Block\Onepage;

use Amasty\CheckoutCore\Model\AdditionalFields;
use Amasty\CheckoutCore\Model\AdditionalFieldsManagement;
use Amasty\CheckoutCore\Model\Config;
use Amasty\CheckoutCore\Model\Config\Source\CustomerRegistration;
use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Newsletter\Model\Subscriber;

/**
 * Additional Layout processor with all private and dynamic data
 *
 * @since 3.0.0
 */
class CustomerProcessor implements LayoutProcessorInterface
{
    /**
     * @var Subscriber
     */
    private $subscriber;

    /**
     * @var AdditionalFieldsManagement
     */
    private $additionalFieldsManagement;

    /**
     * @var Config
     */
    private $checkoutConfig;

    /**
     * @var LayoutWalker
     */
    private $walker;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var LayoutWalkerFactory
     */
    private $walkerFactory;

    public function __construct(
        Subscriber $subscriber,
        AdditionalFieldsManagement $additionalFieldsManagement,
        Config $checkoutConfig,
        LayoutWalkerFactory $walkerFactory,
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession
    ) {
        $this->subscriber = $subscriber;
        $this->additionalFieldsManagement = $additionalFieldsManagement;
        $this->checkoutConfig = $checkoutConfig;
        $this->walkerFactory = $walkerFactory;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Process js Layout of block
     *
     * @param array $jsLayout
     *
     * @return array
     */
    public function process($jsLayout)
    {
        if (!$this->checkoutConfig->isEnabled()) {
            return $jsLayout;
        }
        $this->walker = $this->walkerFactory->create(['layoutArray' => $jsLayout]);

        $this->processAdditionalStepLayout();

        return $this->walker->getResult();
    }

    /**
     * Additional fields in the Summary Block (Review Block)
     */
    protected function processAdditionalStepLayout()
    {
        $fieldsValue = $this->additionalFieldsManagement->getByQuoteId($this->checkoutSession->getQuoteId());
        $this->processNewsletterLayout($fieldsValue);

        if (!$this->checkoutConfig->getAdditionalOptions('comment')) {
            $this->walker->unsetByPath('{ADDITIONAL_STEP}.>>.comment');
        } elseif ($fieldsValue->getComment()) {
            $this->walker->setValue('{ADDITIONAL_STEP}.>>.comment.default', $fieldsValue->getComment());
        }

        if ($this->checkoutConfig->getAdditionalOptions('create_account') === CustomerRegistration::NO
            || $this->checkoutSession->getQuote()->getCustomer()->getId() !== null
        ) {
            $this->walker->unsetByPath('{ADDITIONAL_STEP}.>>.checkboxes.>>.register');
            $this->walker->unsetByPath('{ADDITIONAL_STEP}.>>.checkboxes.>>.date_of_birth');
        } else {
            if (!$this->checkoutConfig->canShowDob()) {
                $this->walker->unsetByPath('{ADDITIONAL_STEP}.>>.checkboxes.>>.date_of_birth');
            } elseif ($fieldsValue->getDateOfBirth()) {
                $this->walker->setValue(
                    '{ADDITIONAL_STEP}.>>.checkboxes.>>.date_of_birth.default',
                    $fieldsValue->getDateOfBirth()
                );
            }

            if ($this->checkoutConfig->getAdditionalOptions('create_account') === CustomerRegistration::AFTER_PLACING) {
                $registerChecked = (bool)$this->checkoutConfig->getAdditionalOptions('create_account_checked');
                if ($fieldsValue->getRegister() !== null) {
                    $registerChecked = (bool)$fieldsValue->getRegister();
                }

                $this->walker->setValue('{ADDITIONAL_STEP}.>>.checkboxes.>>.register.checked', $registerChecked);
                if ($registerChecked) {
                    $this->walker->setValue('{ADDITIONAL_STEP}.>>.checkboxes.>>.register.value', $registerChecked);
                }

                $fieldsValue->setRegister($registerChecked);
            } else {
                $this->walker->unsetByPath('{ADDITIONAL_STEP}.>>.checkboxes.>>.register');
                $registerChecked = true;
            }

            $this->walker->setValue('{ADDITIONAL_STEP}.>>.checkboxes.>>.date_of_birth.visible', $registerChecked);
        }

        $fieldsValue->save();
    }

    /**
     * Visibility and status if the subscribe checkbox
     *
     * @param AdditionalFields $fieldsValue
     */
    private function processNewsletterLayout($fieldsValue)
    {
        $newsletterConfig = (bool)$this->checkoutConfig->getAdditionalOptions('newsletter');

        if ($newsletterConfig && $this->customerSession->isLoggedIn()) {
            $customerId = $this->customerSession->getCustomerId();
            //TODO move to dynamic processor
            $this->subscriber->loadByCustomerId($customerId);
            $newsletterConfig = !$this->subscriber->isSubscribed();
        }

        if (!$newsletterConfig) {
            $this->walker->unsetByPath('{ADDITIONAL_STEP}.>>.checkboxes.>>.subscribe');
        } else {
            $subscribeCheck = (bool)$this->checkoutConfig->getAdditionalOptions('newsletter_checked');
            if ($fieldsValue->getSubscribe() !== null) {
                $subscribeCheck = (bool)$fieldsValue->getSubscribe();
            }
            $this->walker->setValue('{ADDITIONAL_STEP}.>>.checkboxes.>>.subscribe.checked', $subscribeCheck);
            if ($subscribeCheck) {
                $this->walker->setValue('{ADDITIONAL_STEP}.>>.checkboxes.>>.subscribe.value', $subscribeCheck);
            }

            $fieldsValue->setSubscribe($subscribeCheck);
        }
    }
}
