<?php
namespace Vnecoms\VendorsProduct\Model;

use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\Framework\Exception\LocalizedException;

class Queue
{
    /**
     * @var \Magento\Framework\Bulk\BulkManagementInterface
     */
    private $bulkManagement;

    /**
     * @var \Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory
     */
    private $operationFactory;

    /**
     * @var \Magento\Framework\DataObject\IdentityGeneratorInterface
     */
    private $identityService;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    /**
     * @var \Magento\Authorization\Model\UserContextInterface
     */
    private $userContext;

    /**
     * Queue constructor.
     * @param \Magento\Framework\Bulk\BulkManagementInterface $bulkManagement
     * @param \Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory $operartionFactory
     * @param \Magento\Framework\DataObject\IdentityGeneratorInterface $identityService
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param \Magento\Authorization\Model\UserContextInterface $userContext
     */
    public function __construct(
        \Magento\Framework\Bulk\BulkManagementInterface $bulkManagement,
        \Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory $operartionFactory,
        \Magento\Framework\DataObject\IdentityGeneratorInterface $identityService,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Magento\Authorization\Model\UserContextInterface $userContext
    ) {
        $this->bulkManagement = $bulkManagement;
        $this->operationFactory = $operartionFactory;
        $this->identityService = $identityService;
        $this->serializer = $serializer;
        $this->userContext = $userContext;
    }

    /**
     * @param $type
     * @param $dataToUpdate
     * @param $vendorId
     * @param $productId
     * @throws LocalizedException
     */
    public function publish(
        $type,
        $dataToUpdate,
        $vendorId,
        $productId
    ):void {
        $bulkUuid = $this->identityService->generateId();
        $operations[] = $this->makeOperation(
            'Send Email Product Approval',
            'vendor_product.approval_email',
            $dataToUpdate,
            $vendorId,
            $productId,
            $type,
            $bulkUuid
        );

        if (!empty($operations)) {
            $result = $this->bulkManagement->scheduleBulk(
                $bulkUuid,
                $operations,
                'Send Email Product Approval',
                $this->userContext->getUserId()
            );
            if (!$result) {
                throw new LocalizedException(
                    __('Something went wrong while processing the request.')
                );
            }
        }
    }

    /**
     * @param $meta
     * @param $queue
     * @param $dataToUpdate
     * @param $vendorId
     * @param $productId
     * @param $type
     * @param $bulkUuid
     * @return OperationInterface
     */
    private function makeOperation(
        $meta,
        $queue,
        $dataToUpdate,
        $vendorId,
        $productId,
        $type,
        $bulkUuid
    ): OperationInterface {
        $dataToEncode = [
            'meta_information' => $meta,
            'vendor_id' => $vendorId,
            'product_id' => $productId,
            'type' => $type,
            'addition_data' => $dataToUpdate
        ];
        $data = [
            'data' => [
                'bulk_uuid' => $bulkUuid,
                'topic_name' => $queue,
                'serialized_data' => $this->serializer->serialize($dataToEncode),
                'status' => \Magento\Framework\Bulk\OperationInterface::STATUS_TYPE_OPEN,
            ]
        ];

        return $this->operationFactory->create($data);
    }
}
