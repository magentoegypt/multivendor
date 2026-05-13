<?php
/**
 * Created by PhpStorm.
 * User: nvhai
 * Date: 12/26/2016
 * Time: 10:07 AM
 */
namespace Vnecoms\RMA\Model;

/**
 * Factory class for @see \Magento\SalesRule\Model\Rule
 */
class StatusFactory
{
    /**
     * Object Manager instance
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager = null;

    /**
     * Instance name to create
     *
     * @var string
     */
    protected $_instanceName = null;

    /**
     * Factory constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param string $instanceName
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager, $instanceName = '\\Vnecoms\\RMA\\Model\\Status')
    {
        $this->_objectManager = $objectManager;
        $this->_instanceName = $instanceName;
    }

    /**
     * Create class instance with specified parameters
     *
     * @param array $data
     * @return \Vnecoms\RMA\Model\Status
     */
    public function create(array $data = [])
    {
        return $this->_objectManager->create($this->_instanceName, $data);
    }
}
