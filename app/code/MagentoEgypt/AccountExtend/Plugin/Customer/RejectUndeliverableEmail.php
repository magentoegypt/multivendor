<?php
/**
 * A customer account cannot be created with, or changed to, an email whose domain cannot receive mail.
 *
 * CL036-TC97: QA registered amira@magentoegypt.co, a domain with no MX and no A
 * record. Every way a customer account comes into being — the storefront
 * sign-up, REST POST /V1/customers, the GraphQL createCustomer the app uses, the
 * vendor app's seller registration — ends in AccountManagement saving through
 * CustomerRepository, and so does the storefront's "change email". One guard
 * here covers all of them.
 *
 * Scoped to customer-facing callers. Registered only in the frontend, REST,
 * SOAP and GraphQL areas (never adminhtml, crontab or CLI), and within the web
 * API it stands aside for admin and integration tokens: an admin correcting a
 * customer, or the Odoo sync, is never blocked by a DNS answer. Only a change of
 * email is checked on an existing customer, so saving an old account whose
 * domain has since died still works.
 *
 * Fails open: an address whose domain DNS could not resolve is accepted.
 */
declare(strict_types=1);

namespace MagentoEgypt\AccountExtend\Plugin\Customer;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\InputException;
use MagentoEgypt\AccountExtend\Model\EmailDeliverability;
use MagentoEgypt\AccountExtend\Model\UndeliverableEmailMessage;

class RejectUndeliverableEmail
{
    public function __construct(
        private readonly EmailDeliverability $deliverability,
        private readonly ResourceConnection $resource,
        private readonly UserContextInterface $userContext
    ) {
    }

    /**
     * @param CustomerRepositoryInterface $subject
     * @param CustomerInterface $customer
     * @param string|null $passwordHash
     * @return null
     * @throws InputException
     */
    public function beforeSave(CustomerRepositoryInterface $subject, CustomerInterface $customer, $passwordHash = null)
    {
        if (in_array(
            (int) $this->userContext->getUserType(),
            [UserContextInterface::USER_TYPE_ADMIN, UserContextInterface::USER_TYPE_INTEGRATION],
            true
        )) {
            return null;
        }

        $email = trim((string) $customer->getEmail());
        if ($email === '' || !$this->emailIsNew($customer, $email)) {
            return null;
        }

        $check = $this->deliverability->check($email);
        if ($check['deliverable'] === false) {
            throw new InputException(UndeliverableEmailMessage::from($check));
        }

        return null;
    }

    private function emailIsNew(CustomerInterface $customer, string $email): bool
    {
        if (!$customer->getId()) {
            return true;
        }

        $connection = $this->resource->getConnection();
        $stored = (string) $connection->fetchOne(
            $connection->select()
                ->from($this->resource->getTableName('customer_entity'), ['email'])
                ->where('entity_id = ?', (int) $customer->getId())
        );

        return strcasecmp($stored, $email) !== 0;
    }
}
