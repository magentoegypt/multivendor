<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Plugin\Checkout\Controller\Onepage;

use Amasty\CheckoutCore\Api\AdditionalFieldsManagementInterface;
use Amasty\CheckoutCore\Model\Account;
use Amasty\CheckoutCore\Model\AdditionalFields;
use Amasty\CheckoutCore\Model\Config;
use Amasty\CheckoutCore\Model\Customer\Address\IgnoreValidationFlag;
use Amasty\CheckoutCore\Model\CustomerValidator;
use Magento\Checkout\Controller\Onepage\Success;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;

class SuccessPlugin
{
    /**
     * @var Account
     */
    private $account;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var AdditionalFieldsManagementInterface
     */
    private $fieldsManagement;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var int
     */
    private $orderId;

    /**
     * @var AdditionalFields
     */
    private $fields;

    /**
     * @var IgnoreValidationFlag
     */
    private $ignoreValidationFlag;

    public function __construct(
        Account $account,
        Config $config,
        AdditionalFieldsManagementInterface $fieldsManagement,
        Session $session,
        DataPersistorInterface $dataPersistor,
        ManagerInterface $messageManager,
        IgnoreValidationFlag $ignoreValidationFlag
    ) {
        $this->account = $account;
        $this->config = $config;
        $this->fieldsManagement = $fieldsManagement;
        $this->session = $session;
        $this->dataPersistor = $dataPersistor;
        $this->messageManager = $messageManager;
        $this->ignoreValidationFlag = $ignoreValidationFlag;
    }

    /**
     * @param Success $subject
     * @return null
     */
    public function beforeExecute(Success $subject)
    {
        if ($errors = $this->dataPersistor->get(CustomerValidator::ERROR_SESSION_INDEX)) {
            $this->messageManager->addExceptionMessage(
                new LocalizedException(__($errors)),
                __('Something went wrong while creating an account. Please contact us so we can assist you.')
            );

            $this->dataPersistor->clear(CustomerValidator::ERROR_SESSION_INDEX);
        }

        if (!$this->config->isEnabled()) {
            return null;
        }

        $order = $this->session->getLastRealOrder();

        if (!$order || $order->getCustomerId()) {
            return null;
        }

        $fields = $this->fieldsManagement->getByQuoteId($order->getQuoteId());

        $this->orderId = $order->getId();
        $this->fields = $fields;

        return null;
    }

    /**
     * @param Success $subject
     * @param \Magento\Framework\View\Result\Page $result
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function afterExecute(Success $subject, $result)
    {
        $fields = $this->fields;
        if ($fields && $fields->getRegister()) {
            $this->ignoreValidationFlag->setShouldIgnore(true);
            $this->account->create($this->orderId, $fields);
        }

        $this->ignoreValidationFlag->setShouldIgnore(false);

        return $result;
    }
}
