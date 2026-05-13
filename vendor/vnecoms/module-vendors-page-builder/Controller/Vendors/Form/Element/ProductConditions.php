<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Vnecoms\VendorsPageBuilder\Controller\Vendors\Form\Element;

use Magento\Rule\Model\Condition\Combine;
use Vnecoms\Vendors\App\Action\Context;
use Vnecoms\Vendors\Controller\Vendors\Action;

/**
 * Responsible for rendering the top-level conditions rule tree using the provided params
 */
class ProductConditions extends Action
{
    /**
     * @var \Magento\CatalogWidget\Model\Rule
     */
    private $rule;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializer;

    /**
     * ProductConditions constructor.
     * @param Context $context
     * @param \Magento\CatalogWidget\Model\Rule $rule
     * @param \Magento\Framework\Serialize\Serializer\Json $serializer
     */
    public function __construct(
        Context $context,
        \Magento\CatalogWidget\Model\Rule $rule,
        \Magento\Framework\Serialize\Serializer\Json $serializer
    ) {
        $this->rule = $rule;
        $this->serializer = $serializer;
        parent::__construct($context);
    }

    /**
     * @return void
     */
    public function execute()
    {
        $prefix = $this->getRequest()->getParam('prefix', 'conditions');
        $conditionsEncoded = $this->getRequest()->getParam('conditions');
        $conditions = $this->rule->getConditions();
        $conditions->setData('prefix', $prefix);
        // The rule class expects something to be set in the prefix field before the conditions are loaded
        $conditions->setData($prefix, []);
        $this->rule->loadPost(['conditions' => $this->serializer->unserialize($conditionsEncoded)]);
        $formName = $this->getRequest()->getParam('form_namespace');
        // Combine class recursively sets jsFormObject so we don't need to
        $conditions->setJsFormObject($this->getRequest()->getParam('js_object_name'));
        // The Combine class doesn't need the data attribute on children but we do.
        $this->configureConditionsFormName($conditions, $formName);
        $result = $conditions->asHtmlRecursive();
        $this->getResponse()->setBody($result);
    }

    /**
     * Recursively set form name for data-form-part to be set on all conditions HTML
     *
     * @param Combine $conditions
     * @param string $formName
     * @return void
     */
    private function configureConditionsFormName(Combine $conditions, string $formName): void
    {
        $conditions->setFormName($formName);

        foreach ($conditions->getConditions() as $condition) {
            if ($condition instanceof Combine) {
                $this->configureConditionsFormName($condition, $formName);
            } else {
                $condition->setFormName($formName);
            }
        }
    }
}
