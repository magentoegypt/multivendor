<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout for Magento 2
 */

namespace Amasty\Checkout\Plugin\Controller\Adminhtml\Field;

use Amasty\Checkout\Model\SaveFields;
use Amasty\CheckoutCore\Controller\Adminhtml\Field\Save as FieldSave;
use Amasty\CheckoutCore\Model\Field;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Store\Model\ScopeInterface;

class Save
{
    /**
     * @var SaveFields
     */
    private $saveFields;

    public function __construct(
        SaveFields $saveFields
    ) {
        $this->saveFields = $saveFields;
    }

    /**
     * @param FieldSave $subject
     * @param Redirect $result
     * @return Redirect
     * @throws CouldNotDeleteException
     * @throws CouldNotSaveException
     */
    public function afterExecute(FieldSave $subject, Redirect $result): Redirect
    {
        $fields = $subject->getRequest()->getParam('field');
        $storeId = (int) $subject->getRequest()->getParam(ScopeInterface::SCOPE_STORE, Field::DEFAULT_STORE_ID);
        $this->saveFields->saveFields((array)$fields, $storeId);

        return $result;
    }
}
