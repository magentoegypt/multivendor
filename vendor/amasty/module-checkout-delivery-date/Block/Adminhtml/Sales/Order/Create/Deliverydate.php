<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Delivery Date for Magento 2 (System)
 */

namespace Amasty\CheckoutDeliveryDate\Block\Adminhtml\Sales\Order\Create;

use Amasty\CheckoutDeliveryDate\Model\ConfigProvider;
use Amasty\CheckoutDeliveryDate\Model\DeliveryDateProvider;
use Magento\Backend\Model\Session\Quote;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Deliverydate extends Template
{
    /**
     * @var DeliveryDateProvider
     */
    private $deliveryProvider;

    /**
     * @var FormFactory
     */
    private $formFactory;

    /**
     * @var Quote
     */
    private $sessionQuote;

    /**
     * @var DateTime
     */
    private $date;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(
        Context $context,
        FormFactory $formFactory,
        DeliveryDateProvider $deliveryProvider,
        Quote $sessionQuote,
        DateTime $date,
        ConfigProvider $configProvider,
        array $data = []
    ) {
        $this->formFactory = $formFactory;
        $this->deliveryProvider = $deliveryProvider;
        $this->sessionQuote = $sessionQuote;
        $this->date = $date;
        $this->configProvider = $configProvider;
        parent::__construct($context, $data);
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _construct()
    {
        parent::_construct();

        $this->setTemplate('Amasty_CheckoutDeliveryDate::sales/order/delivery_create.phtml');
    }

    public function getFormElements()
    {
        $form = $this->formFactory->create();
        $form->setHtmlIdPrefix('amasty_checkout_deliverydate_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Delivery'),
                'class' => 'amasty-checkout-deliverydate-fieldset'
            ]
        );

        $fieldset->addField(
            'date',
            'date',
            [
                'label' => __('Delivery Date'),
                'name' => 'am_checkout_deliverydate[date]',
                'input_format' => \Magento\Framework\Stdlib\DateTime::DATE_INTERNAL_FORMAT,
                'style' => 'width: 40%',
                'format' => 'y-MM-dd',
                'required' => $this->configProvider->isDeliveryDateRequired(),
                'date_format' => 'y-MM-dd',
                'min_date' => $this->date->date('c'),
                'value' => null
            ]
        );

        $deliveryHours = $this->getDeliveryHours();
        $fieldset->addField(
            'time',
            'select',
            [
                'label' => __('Delivery Time Interval'),
                'name' => 'am_checkout_deliverydate[time]',
                'style' => 'width: 40%',
                'required' => false,
                'value' => null,
                'options' => $deliveryHours,
            ]
        );

        if ($this->configProvider->isCommentEnabled()) {
            $fieldset->addField(
                'comment',
                'textarea',
                [
                    'label' => __('Delivery Comment'),
                    'title' => __('Delivery Comment'),
                    'name' => 'am_checkout_deliverydate[comment]',
                    'required' => false,
                    'style' => 'width: 40%',
                    'placeholder' => $this->configProvider->getDefaultComment()
                ]
            );
        }

        $data = $this->getDeliveryInfo();

        if (!empty($data)) {
            if (isset($data['date']) && '0000-00-00' == $data['date']) {
                $data['date'] = '';
            }

            if (isset($data['time']) && !isset($deliveryHours[$data['time']])) {
                $data['time'] = -1;
            }

            $form->setValues($data);
        }

        return $form->getElements();
    }

    public function getDeliveryInfo()
    {
        $orderId = 0;

        if ($this->sessionQuote->getOrderId()) { // edit order
            $orderId = (int)$this->sessionQuote->getOrderId();
        } elseif ($this->sessionQuote->getReordered()) { // reorder
            $orderId = (int)$this->sessionQuote->getReordered();
        }

        $delivery = $this->deliveryProvider->findByOrderId($orderId);

        return $delivery->getData();
    }

    /**
     * @return array
     */
    public function getDeliveryHours(): array
    {
        $options = $this->configProvider->getDeliveryHours();
        if (empty($options)) {
            return $options;
        }

        $deliveryHours = [];
        foreach ($options as $option) {
            $deliveryHours[$option['value']] = $option['label'];
        }

        return $deliveryHours;
    }
}
