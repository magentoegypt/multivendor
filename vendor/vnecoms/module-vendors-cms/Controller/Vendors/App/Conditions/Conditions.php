<?php


namespace Vnecoms\VendorsCms\Controller\Vendors\App\Conditions;

use Magento\Rule\Model\Condition\AbstractCondition;

/**
 * Class Conditions.
 */
class Conditions extends \Vnecoms\VendorsCms\Controller\Vendors\App
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsCms::app';
    /**
     * @var \Magento\CatalogWidget\Model\Rule
     */
    protected $rule;

    /**
     * Conditions constructor.
     * @param \Vnecoms\Vendors\App\Action\Context $context
     * @param \Vnecoms\VendorsCms\Model\AppFactory $appFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Math\Random $mathRandom
     * @param \Magento\CatalogWidget\Model\Rule $rule
     * @param \Magento\Framework\Translate\InlineInterface $translateInline
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Vnecoms\Vendors\App\Action\Context $context,
        \Vnecoms\VendorsCms\Model\AppFactory $appFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Math\Random $mathRandom,
        \Magento\CatalogWidget\Model\Rule $rule,
        \Magento\Framework\Translate\InlineInterface $translateInline,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->rule = $rule;
        parent::__construct($context, $appFactory, $logger, $mathRandom, $translateInline, $scopeConfig);
    }

    /**
     */
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
