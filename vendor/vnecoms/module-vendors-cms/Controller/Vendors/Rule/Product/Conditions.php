<?php

namespace Vnecoms\VendorsCms\Controller\Vendors\Rule\Product;

use Magento\Rule\Model\Condition\AbstractCondition;
use Vnecoms\Vendors\App\Action\Context;

/**
 * Class Conditions.
 */
class Conditions extends \Vnecoms\Vendors\Controller\Vendors\Action
{
    /**
     * Authorization level of a basic admin session.
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsCms::index';
    /**
     * @var \Vnecoms\VendorsCms\Model\Rule\Rule
     */
    protected $rule;

    /**
     * Conditions constructor.
     *
     * @param \Vnecoms\Vendors\App\Action\Context|\Vnecoms\Vendors\Controller\Vendors\Context $context
     * @param \Vnecoms\VendorsCms\Model\Rule\Rule                                             $rule
     */
    public function __construct(Context $context, \Vnecoms\VendorsCms\Model\Rule\Rule $rule)
    {
        parent::__construct($context);
        $this->rule = $rule;
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $typeData = explode('|', str_replace('-', '/', $this->getRequest()->getParam('type')));
        $className = $typeData[0];

        $model = $this->_objectManager->create($className)
            ->setId($id)
            ->setType($className)
            ->setRule($this->rule)
            ->setPrefix('conditions');

        if (!empty($typeData[1])) {
            $model->setAttribute($typeData[1]);
        }

        $result = '';
        if ($model instanceof AbstractCondition) {
            $model->setJsFormObject($this->getRequest()->getParam('form'));
            $result = $model->asHtmlRecursive();
        }
        $this->getResponse()->setBody($result);
    }
}
