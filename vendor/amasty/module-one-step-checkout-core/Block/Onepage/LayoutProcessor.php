<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Block\Onepage;

use Amasty\CheckoutCore\Model\Amazon\Config as AmazonConfig;
use Amasty\CheckoutCore\Model\CheckoutConfigProvider\Gdpr\ConsentsProvider;
use Amasty\CheckoutCore\Model\Config;
use Amasty\CheckoutCore\Model\CustomerAttribute\Storage;
use Amasty\CheckoutCore\Model\LayoutProcessor\SortFields;
use Amasty\CheckoutCore\Model\ModuleEnable;
use Amasty\CheckoutCore\Plugin\Checkout\Block\Checkout\AttributeMerger;
use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\ObjectManager;

/**
 * Checkout Layout Processor
 * Set Default values, field positions
 * @since 3.0.0 refactored for being cached
 */
class LayoutProcessor implements LayoutProcessorInterface
{
    public const GDPR_COMPONENT_NAME = 'amasty-gdpr-consent';

    /**
     * @var AttributeMerger
     */
    private $attributeMerger;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var Config
     */
    private $checkoutConfig;

    /**
     * @var ModuleEnable
     */
    private $moduleEnable;

    /**
     * @var LayoutWalkerFactory
     */
    private $walkerFactory;

    /**
     * @var LayoutWalker
     */
    private $walker;

    /**
     * @var ConsentsProvider
     */
    private $consentsProvider;

    /**
     * @var SortFields
     */
    private $sortFields;

    /**
     * @var AmazonConfig
     */
    private $amazonConfig;

    /**
     * @var Storage
     */
    private $customerAttributeStorage;

    public function __construct(
        AttributeMerger $attributeMerger,
        CustomerSession $customerSession,
        Config $checkoutConfig,
        ModuleEnable $moduleEnable,
        LayoutWalkerFactory $walkerFactory,
        ConsentsProvider $consentsProvider,
        SortFields $sortFields,
        AmazonConfig $amazonConfig,
        Storage $customerAttributeStorage = null // TODO: move to not optional argument and remove OM
    ) {
        $this->attributeMerger = $attributeMerger;
        $this->customerSession = $customerSession;
        $this->checkoutConfig = $checkoutConfig;
        $this->moduleEnable = $moduleEnable;
        $this->walkerFactory = $walkerFactory;
        $this->consentsProvider = $consentsProvider;
        $this->sortFields = $sortFields;
        $this->amazonConfig = $amazonConfig;
        $this->customerAttributeStorage = $customerAttributeStorage
            ?? ObjectManager::getInstance()->get(Storage::class);
    }

    /**
     * @param array $jsLayout
     * @return array
     */
    public function process($jsLayout)
    {
        if (!$this->checkoutConfig->isEnabled()) {
            return $jsLayout;
        }
        $this->walker = $this->walkerFactory->create(['layoutArray' => $jsLayout]);

        $this->setRequiredField();

        if (!$this->checkoutConfig->getAdditionalOptions('discount')) {
            $this->walker->unsetByPath('{PAYMENT}.>>.afterMethods.>>.discount');
        }

        if ($this->moduleEnable->isAmazonPayV2Enable() && !$this->amazonConfig->isV2Enabled()) {
            $this->walker->unsetByPath('{SHIPPING_ADDRESS}.>>.customer-email.>>.amazon-pay-button-region');
        }

        $this->processShippingLayout();

        $this->walker->setValue(
            '{CART_ITEMS}.>>.details.component',
            'Amasty_CheckoutCore/js/view/checkout/summary/item/details'
        );

        if ($this->checkoutConfig->isCheckoutItemsEditable()) {
            $this->walker->setValue(
                '{CART_ITEMS}.component',
                'Amasty_CheckoutCore/js/view/checkout/summary/cart-items'
            );
        }

        $this->walker->setValue(
            'components.checkoutProvider.config.defaultShippingMethod',
            $this->checkoutConfig->getDefaultShippingMethod()
        );
        $this->walker->setValue(
            'components.checkoutProvider.config.defaultPaymentMethod',
            $this->checkoutConfig->getDefaultPaymentMethod()
        );

        $this->walker->setValue(
            'components.checkoutProvider.config.minimumPasswordLength',
            $this->checkoutConfig->getMinimumPasswordLength()
        );

        $this->walker->setValue(
            'components.checkoutProvider.config.requiredCharacterClassesNumber',
            $this->checkoutConfig->getRequiredCharacterClassesNumber()
        );

        $this->walker->setValue(
            '{CART_ITEMS}.>>.details.isAutomatically',
            $this->checkoutConfig->isEditTypeAutomatically()
        );

        $this->walker->setValue(
            '{CART_ITEMS}.>>.details.isShowUnitPrice',
            (bool)$this->checkoutConfig->getAdditionalOptions('unit_price')
        );

        $this->agreementsMoveToReviewBlock();
        $this->processGdprCheckboxes();
        $this->moveDiscountToReviewBlock();
        $this->moveTotalToEnd();

        $fields = $this->walker->getValue('{SHIPPING_ADDRESS_FIELDSET}.>>');
        $this->prepareFields($fields);
        $this->sortFields->execute($fields);
        $this->hideCountryIdField($fields);
        $this->walker->setValue(
            '{SHIPPING_ADDRESS_FIELDSET}.>>',
            $fields
        );

        return $this->walker->getResult();
    }

    /**
     * Shipping address component and shipping form processor
     */
    private function processShippingLayout()
    {
        if (!$this->checkoutConfig->getMultipleShippingAddress() || !$this->customerSession->isLoggedIn()) {
            /*
             * Remove shipping information from sidebar,
             * on onestep checkout customer already see shipping information.
             *
             * But it is used for dropdown shipping address list
             */
            $this->walker->unsetByPath('{SIDEBAR}.>>.shipping-information');
        } else {
            //remove all ship-to children to avid unnecessary information (Delivery Date compatibility)
            $this->walker->unsetByPath('{SIDEBAR}.>>.shipping-information.>>.ship-to.>>');
        }
    }

    /**
     * The method sets field as required
     */
    private function setRequiredField()
    {
        $attributeConfig = $this->attributeMerger->getFieldConfig();

        if (isset($attributeConfig['postcode'])) {
            $this->walker->setValue(
                '{SHIPPING_ADDRESS_FIELDSET}.>>.postcode.skipValidation',
                !$attributeConfig['postcode']->getData('required')
            );

            if ($this->walker->isExist('{SHIPPING_ADDRESS_FIELDSET}.>>.postcode.validation.required-entry')) {
                $this->walker->setValue(
                    '{SHIPPING_ADDRESS_FIELDSET}.>>.postcode.skipValidation',
                    !$this->walker->getValue('{SHIPPING_ADDRESS_FIELDSET}.>>.postcode.validation.required-entry')
                );
            }
        }

        $componentsData = [
            '{SHIPPING_ADDRESS_FIELDSET}.>>' => null,
            '{PAYMENT}.>>.afterMethods.>>.billing-address-form.>>.form-fields.>>' => null
        ];

        foreach ($componentsData as $path => $componentFields) {
            $componentsData[$path] = $this->walker->getValue($path);
        }

        foreach ($attributeConfig as $field => $config) {
            foreach ($componentsData as $path => $componentFields) {
                if (isset($componentsData[$path][$field])
                    && !isset($componentsData[$path][$field]['skipValidation'])
                ) {
                    $componentsData[$path][$field]['skipValidation'] = !$config->getIsRequired();
                    $componentsData[$path][$field]['validation']['required-entry'] = $config->getIsRequired();
                }
            }
        }

        foreach ($componentsData as $path => $componentFields) {
            $this->walker->setValue($path, $componentsData[$path]);
        }
    }

    /**
     * The method moves to review block
     */
    private function agreementsMoveToReviewBlock()
    {
        $paymentListComponent = $this->walker->getValue('{PAYMENT}.>>.payments-list.>>.before-place-order');

        if ($paymentListComponent) {
            $checkedAgreement = $this->checkoutConfig->isSetAgreements();
            $agreementsHasToMove = $this->checkoutConfig->getPlaceDisplayTermsAndConditions();

            if ($checkedAgreement) {
                if ($agreementsHasToMove == Config::VALUE_ORDER_TOTALS) {
                    $agreementComponent = [
                        'agreements' => $paymentListComponent['children']['agreements']
                    ];

                    $this->walker->unsetByPath('{PAYMENT}.>>.payments-list.>>.before-place-order.>>.agreements');
                    $additionalCheckboxes = $this->walker->getValue('{ADDITIONAL_STEP}.>>.checkboxes.>>');
                    $additionalCheckboxes = array_merge($agreementComponent, $additionalCheckboxes);
                    $this->walker->setValue('{ADDITIONAL_STEP}.>>.checkboxes.>>', $additionalCheckboxes);
                }

                //replace agreement validation
                $this->walker->setValue(
                    '{PAYMENT}.>>.additional-payment-validators.>>.agreements-validator.component',
                    'Amasty_CheckoutCore/js/view/validators/agreement-validation'
                );
            }
        }
    }

    /**
     * The method process Amasty_Gdpr module checkboxes
     */
    private function processGdprCheckboxes(): void
    {
        $gdprComponent = $this->walker->getValue(
            '{PAYMENT}.>>.payments-list.>>.before-place-order.>>.' . self::GDPR_COMPONENT_NAME
        );

        if ($gdprComponent && $this->moduleEnable->isGdprEnable()) {
            $consentsConfig = $this->consentsProvider->getConsentsConfig();
            $oneStep = $this->walker->getValue('{CHECKOUT}');

            if (!empty($consentsConfig) && !empty($oneStep)) {
                $gdprComponent['name'] = self::GDPR_COMPONENT_NAME;
                $gdprComponent['component'] = 'Amasty_CheckoutCore/js/view/gdpr-consent';
                unset($gdprComponent['displayArea']);
                $oneStep['config']['gdprComponentTmpl'] = $gdprComponent;
                $oneStep['config']['gdprTml'] = 'Amasty_CheckoutCore/onepage/gdpr-container';

                $this->walker->setValue('{CHECKOUT}', $oneStep);
                $this->walker->unsetByPath(
                    '{PAYMENT}.>>.payments-list.>>.before-place-order.>>.' . self::GDPR_COMPONENT_NAME
                );
            }
        }
    }

    /**
     * The method moves discount inputs (coupons, rewards, etc.) to review block
     */
    private function moveDiscountToReviewBlock()
    {
        $summaryAdditional = [];
        $summaryAdditional['discount'] = $this->walker->getValue('{PAYMENT}.>>.afterMethods.>>.discount');
        $this->walker->unsetByPath('{PAYMENT}.>>.afterMethods.>>.discount');
        $summaryAdditional['rewards'] = $this->walker->getValue('{PAYMENT}.>>.afterMethods.>>.rewards');
        $this->walker->unsetByPath('{PAYMENT}.>>.afterMethods.>>.rewards');
        $summaryAdditional['gift-card'] = $this->walker->getValue('{PAYMENT}.>>.afterMethods.>>.gift-card');
        $this->walker->unsetByPath('{PAYMENT}.>>.afterMethods.>>.gift-card');

        $summaryAdditional = array_filter($summaryAdditional);
        $this->walker->setValue('{SIDEBAR}.>>.summary_additional.>>', $summaryAdditional);
    }

    /**
     * Move totals to the end of summary block
     */
    private function moveTotalToEnd()
    {
        $summary = $this->walker->getValue('{SIDEBAR}.>>.summary.>>');
        $totalsSection = $summary['totals'];
        unset($summary['totals']);
        $summary['totals'] = $totalsSection;
        $this->walker->setValue('{SIDEBAR}.>>.summary.>>', $summary);
    }

    /**
     * @param array $fields
     */
    private function prepareFields(&$fields)
    {
        foreach ($fields as $code => $field) {
            if ($code === 'customer_attributes_renderer' || str_contains($code, 'order-attributes-fields')) {
                foreach ($field['children'] as $attributeCode => $attribute) {
                    $fields[$attributeCode] = $attribute;

                    if ($code === 'customer_attributes_renderer') {
                        $fields[$attributeCode]['sortOrder'] -= 2000;
                        $this->customerAttributeStorage->addCustomerAttributeCode($attributeCode);
                    }

                    $fields[$code]['fields'][] = $attributeCode;
                }

                unset($fields[$code]['children']);
            }
        }
    }

    /**
     * Hide country_id field, if it's disabled in "Manage Checkout Fields"
     *
     * @param $fields
     */
    private function hideCountryIdField(&$fields)
    {
        $attributeConfig = $this->attributeMerger->getFieldConfig();

        foreach ($fields as $code => $item) {
            if ($code === 'country_id' && isset($attributeConfig[$code])) {
                $fields['country_id']['visible'] = (bool)$attributeConfig[$code]->getEnabled();
            }
        }
    }
}
