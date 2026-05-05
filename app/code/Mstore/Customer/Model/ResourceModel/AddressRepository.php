<?php declare(strict_types=1);

namespace Mstore\Customer\Model\ResourceModel;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\AddressSearchResultsInterfaceFactory;
use Magento\Customer\Model\AddressRegistry;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\ResourceModel\Address;
use Magento\Customer\Model\ResourceModel\Address\Collection;
use Magento\Customer\Model\ResourceModel\Address\CollectionFactory;
use Magento\Customer\Model\ResourceModel\AddressRepository as MageAddressRepository;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Mstore\Customer\Api\AddressRepositoryInterface;

class AddressRepository implements AddressRepositoryInterface
{
    /**
     * @var CustomerRepository
     */
    private $customerRepository;

    /**
     * @var MageAddressRepository
     */
    private $mageAddressRepository;

    /**
     * @var AddressRegistry
     */
    private $addressRegistry;

    /**
     * @var CustomerRegistry
     */
    private $customerRegistry;

    /**
     * @var Address
     */
    private $addressResourceModel;

    /**
     * @var AddressSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var CollectionFactory
     */
    private $addressCollectionFactory;

    /**
     * @var JoinProcessorInterface
     */
    private $extensionAttributesJoinProcessor;

    /**
     * AddressRepository constructor.
     * @param CustomerRepository $customerRepository
     * @param MageAddressRepository $mageAddressRepository
     * @param AddressRegistry $addressRegistry
     * @param CustomerRegistry $customerRegistry
     * @param Address $addressResourceModel
     * @param AddressSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param CollectionFactory $addressCollectionFactory
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     */
    public function __construct(
        CustomerRepository $customerRepository,
        MageAddressRepository $mageAddressRepository,
        AddressRegistry $addressRegistry,
        CustomerRegistry $customerRegistry,
        Address $addressResourceModel,
        AddressSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor,
        CollectionFactory $addressCollectionFactory,
        JoinProcessorInterface $extensionAttributesJoinProcessor
    ) {
        $this->customerRepository = $customerRepository;
        $this->mageAddressRepository = $mageAddressRepository;
        $this->addressRegistry = $addressRegistry;
        $this->customerRegistry = $customerRegistry;
        $this->addressResourceModel = $addressResourceModel;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->addressCollectionFactory = $addressCollectionFactory;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save($customerId, $address)
    {
        if ($address->getId()) {
            if (!in_array($address->getId(), $this->getAddressCollectionByCustomerId($customerId)->getAllIds())) {
                throw new NoSuchEntityException(__('The address does not belong to the current customer.'));
            }
        }

        $address->setCustomerId($customerId)->setRegion($address->getRegion());
        $this->validateData($address);

        try {
            $this->mageAddressRepository->save($address);
        } catch (LocalizedException $e) {
            throw new LocalizedException(__('Cannot save address.'));
        }

        return $address;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($customerId, $addressId)
    {
        $addressCollection = $this->getAddressCollectionByCustomerId($customerId);

        if (in_array($addressId, $addressCollection->getAllIds())) {
            $address = $this->addressRegistry->retrieve($addressId);

            try {
                $addressCollection->removeItemByKey($addressId);
                $this->addressResourceModel->delete($address);
                $this->addressRegistry->remove($addressId);
                return true;
            } catch (\Exception $e) {
                throw new StateException(__('Cannot remove the address.'));
            }
        } else {
            throw new NoSuchEntityException(__('The address does not belong to the current customer.'));
        }
    }

    /**
     * @inheritDoc
     */
    public function getById($customerId, $addressId)
    {
        if (in_array($addressId, $this->getAddressCollectionByCustomerId($customerId)->getAllIds())) {
            $address = $this->addressRegistry->retrieve($addressId);
            return $address->getDataModel();
        } else {
            throw new NoSuchEntityException(__('The address does not belong to the current customer.'));
        }
    }

    /**
     * @inheritDoc
     */
    public function getList($customerId, \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        $searchResults = $this->searchResultsFactory->create();
        $customerModel = $this->customerRegistry->retrieve($customerId);
        $searchResults->setSearchCriteria($searchCriteria);
        /** @var Collection $collection */
        $collection = $this->addressCollectionFactory->create()->setCustomerFilter($customerModel);
        $this->extensionAttributesJoinProcessor->process($collection, AddressInterface::class);
        $this->collectionProcessor->process($searchCriteria, $collection);
        $searchResults->setTotalCount($collection->getSize());
        $addresses = [];
        /** @var \Magento\Customer\Model\Address $addressModel */
        foreach ($collection as $addressModel) {
            $addresses[] = $addressModel->getDataModel();
        }
        $searchResults->setItems($addresses);
        return $searchResults;
    }

    /**
     * @param int $customerId
     * @return Collection
     * @throws NoSuchEntityException
     */
    private function getAddressCollectionByCustomerId($customerId)
    {
        $customerModel = $this->customerRegistry->retrieve($customerId);
        return $customerModel->getAddressesCollection();
    }

    /**
     * @param AddressInterface $address
     * @throws InputException
     */
    private function validateData(AddressInterface $address)
    {
        if (empty($address->getFirstname())) {
            $this->throwException(__('Please enter the first name.'));
        }
        if (empty($address->getLastname())) {
            $this->throwException(__('Please enter the last name.'));
        }
        if (empty($address->getStreet())) {
            $this->throwException(__('Please enter the street.'));
        }
        if (empty($address->getCity())) {
            $this->throwException(__('Please enter the city.'));
        }

        if (empty($address->getTelephone())) {
            $this->throwException(__('Please enter the phone number.'));
        }

        $countryId = $address->getCountryId();

        if (empty($address->getPostcode())) {
            $this->throwException(__('Please enter the zip/postal code.'));
        }
        if (empty($countryId)) {
            $this->throwException(__('Please enter the country.'));
        }
        if (empty($address->getRegionId())) {
            $this->throwException(__('Please enter the state/province.'));
        }
    }

    /**
     * @param $message
     * @throws InputException
     */
    private function throwException($message)
    {
        throw new InputException($message);
    }
}
