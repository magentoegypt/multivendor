<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Block\Adminhtml\Status\Edit;

use Magento\Backend\Block\Widget\Context;
use Vnecoms\RMA\Api\StatusRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class GenericButton
 */
class GenericButton
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var StatusRepositoryInterface
     */
    protected $statusRepository;

    /**
     * @param Context $context
     * @param StatusRepositoryInterface $statusRepository
     */
    public function __construct(
        Context $context,
        StatusRepositoryInterface $statusRepository
    ) {
        $this->context = $context;
        $this->statusRepository = $statusRepository;
    }

    /**
     * Return Status ID
     *
     * @return int|null
     */
    public function getStatusId()
    {
        try {
            return $this->statusRepository->getById(
                $this->context->getRequest()->getParam('status_id')
            )->getId();
        } catch (NoSuchEntityException $e) {
        }
        return null;
    }
    /**
     * Return Status Is Main
     *
     * @return int|null
     */
    public function getIsMain()
    {
        try {
            return $this->statusRepository->getById(
                $this->context->getRequest()->getParam('status_id')
            )->getIsMain();
        } catch (NoSuchEntityException $e) {
        }
        return null;
    }
    /**
     * Generate url by route and parameters
     *
     * @param   string $route
     * @param   array $params
     * @return  string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
