<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout for Magento 2
 */

namespace Amasty\Checkout\Plugin\Sales\Block\Adminhtml\Order\Create\Form\Address;

use Amasty\Checkout\Model\Attributes\AdminPhoneValidationProcessor;
use Magento\Framework\Data\Form;
use Magento\Sales\Block\Adminhtml\Order\Create\Form\Address;

class AddPhoneValidation
{
    private const PHONE_ELEMENT = 'telephone';

    /**
     * @var string[]
     */
    private $processedNames = [];

    /**
     * @var AdminPhoneValidationProcessor
     */
    private $adminPhoneValidationProcessor;

    public function __construct(
        AdminPhoneValidationProcessor $adminPhoneValidationProcessor
    ) {
        $this->adminPhoneValidationProcessor = $adminPhoneValidationProcessor;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetForm(Address $subject, Form $result): Form
    {
        $phoneElement = $result->getElement(self::PHONE_ELEMENT);

        if (!$phoneElement) {
            return $result;
        }

        $elementName = $phoneElement->getName();
        if (!in_array($elementName, $this->processedNames, true)) {
            $phoneClass = $this->adminPhoneValidationProcessor->process($phoneElement->getClass());

            $this->processedNames[] = $elementName;
            $result->getElement(self::PHONE_ELEMENT)->setClass($phoneClass);
        }

        return $result;
    }
}
