<?php

namespace Vnecoms\Quotation\Test\Unit\Model;

use Vnecoms\Quotation\Model\Quote;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class QuoteTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    /**
     * @var \Vnecoms\Quotation\Model\Quote
     */
    protected $quote;

    protected $resourceMock;

    protected $customerMock;

    protected $timezoneMock;

    protected $helperMock;

    protected $configMock;

    protected $quoteItemCollectionFactoryMock;

    protected $messageCollectionFactoryMock;

    protected $quoteItemFactoryMock;

    protected $randomDataGeneratorMock;

    protected $productRepositoryMock;

    protected $emailHelperMock;

    protected function setUp()
    {
        $this->storeManagerMock = $this->getMockBuilder('Magento\Store\Model\StoreManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceMock = $this->getMockBuilder('Vnecoms\Quotation\Model\ResourceModel\Quote')
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock = $this->getMockBuilder('Magento\Framework\Model\Context')
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerMock = $this->getMockBuilder('Magento\Customer\Model\Customer')
        ->disableOriginalConstructor()
        ->getMock();
        $this->timezoneMock = $this->getMockBuilder('Magento\Framework\Stdlib\DateTime\TimezoneInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->helperMock = $this->getMockBuilder('Vnecoms\Quotation\Helper\Data')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configMock = $this->getMockBuilder('Magento\Framework\App\Config\ConfigResource\ConfigInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteItemCollectionFactoryMock = $this->getMockBuilder('Vnecoms\Quotation\Model\ResourceModel\Item\CollectionFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->messageCollectionFactoryMock = $this->getMockBuilder('Vnecoms\Quotation\Model\ResourceModel\Message\CollectionFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteItemFactoryMock = $this->getMockBuilder('Vnecoms\Quotation\Model\ItemFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->randomDataGeneratorMock = $this->getMockBuilder('Magento\Framework\Math\Random')
            ->disableOriginalConstructor()
            ->getMock();
        $this->productRepositoryMock = $this->getMockBuilder('Magento\Catalog\Model\ProductRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->emailHelperMock = $this->getMockBuilder('Vnecoms\Quotation\Helper\Email')
            ->disableOriginalConstructor()
            ->getMock();


        $this->quote = (new ObjectManager($this))
            ->getObject(
                'Vnecoms\Quotation\Model\Quote',
                [
                    'storeManager' => $this->storeManagerMock,
                    'resource' => $this->resourceMock,
                    'context' => $this->contextMock,
                    'customer' => $this->customerMock,
                    'timezone' => $this->timezoneMock,
                    'helper' => $this->helperMock,
                    'config' => $this->configMock,
                    'collectionFactory' => $this->quoteItemCollectionFactoryMock,
                    'messageCollectionFactory' => $this->messageCollectionFactoryMock,
                    'factory' => $this->quoteItemFactoryMock,
                    'randomDataGenerator' => $this->randomDataGeneratorMock,
                    'productRepository' => $this->productRepositoryMock,
                    'emailHelper' => $this->emailHelperMock
                ]
            );
    }


    /**
     * Customer group ID is set to quote object.
     */
    public function testGetCustomerGroupId()
    {
        /** Preconditions */
        $customerGroupId = 33;
        $this->quote->setCustomerGroupId($customerGroupId);

        /** SUT execution */
        $this->assertEquals($customerGroupId, $this->quote->getCustomerGroupId(), "Customer group ID is invalid");
    }

    public function testGetStoreId()
    {
        $storeId = 1;

        $result = $this->quote->setStoreId($storeId)->getStoreId();
        $this->assertEquals($storeId, $result);
    }

    public function testGetStore()
    {
        $storeId = 1;

        $storeMock = $this->getMockBuilder('Magento\Store\Model\Store')
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->with($storeId)
            ->will($this->returnValue($storeMock));

        $this->quote->setStoreId($storeId);
        $result = $this->quote->getStore();
        $this->assertInstanceOf('Magento\Store\Model\Store', $result);
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     */
    public function testAddProductException()
    {
        $this->quote->addProduct($this->productRepositoryMock, 'test');
    }

    public function testAddItem()
    {
        $item = $this->getMock('Vnecoms\Quotation\Model\Item', ['setQuote', 'getId'], [], '', false);
        $item->expects($this->once())
            ->method('setQuote');
        $item->expects($this->once())
            ->method('getId')
            ->willReturn(false);
        $itemsMock = $this->getMock(
            'Magento\Eav\Model\Entity\Collection\AbstractCollection',
            ['setQuote', 'addItem'],
            [],
            '',
            false
        );
        $itemsMock->expects($this->once())
            ->method('setQuote');
        $itemsMock->expects($this->once())
            ->method('addItem')
            ->with($item);
        $this->quoteItemCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($itemsMock);

        $this->quote->addItem($item);
    }

    public function testGetItemsCollection()
    {
        $itemCollectionMock = $this->getMockBuilder('Vnecoms\Quotation\Model\ResourceModel\Item\Collection')
            ->disableOriginalConstructor()
            ->setMethods(['setQuote'])
            ->getMock();
        $this->quoteItemCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($itemCollectionMock);

        $this->extensionAttributesJoinProcessorMock->expects($this->once())
            ->method('process')
            ->with(
                $this->isInstanceOf('Vnecoms\Quotation\Model\ResourceModel\Quote\Collection')
            );
        $itemCollectionMock->expects($this->once())->method('setQuote')->with($this->quote);

        $this->quote->getItemsCollection();
    }

    public function testGetAllItems()
    {
        $itemOneMock = $this->getMockBuilder('Vnecoms\Quotation\Model\ResourceModel\Item')
            ->setMethods(['isDeleted'])
            ->disableOriginalConstructor()
            ->getMock();
        $itemOneMock->expects($this->once())
            ->method('isDeleted')
            ->willReturn(false);

        $itemTwoMock = $this->getMockBuilder('Vnecoms\Quotation\Model\ResourceModel\Item')
            ->setMethods(['isDeleted'])
            ->disableOriginalConstructor()
            ->getMock();
        $itemTwoMock->expects($this->once())
            ->method('isDeleted')
            ->willReturn(true);

        $items = [$itemOneMock, $itemTwoMock];
        $itemResult = [$itemOneMock];
        $this->quote->setData('items_collection', $items);

        $this->assertEquals($itemResult, $this->quote->getAllItems());
    }
}