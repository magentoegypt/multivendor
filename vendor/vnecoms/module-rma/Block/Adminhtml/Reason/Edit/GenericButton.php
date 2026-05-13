<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Block\Adminhtml\Reason\Edit;

use Magento\Backend\Block\Widget\Context;
use Vnecoms\RMA\Api\ReasonRepositoryInterface;
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
     * @var ReasonRepositoryInterface
     */
    protected $reasonRepository;

    /**
     * GenericButton constructor.
     * @param Context $context
     * @param ReasonRepositoryInterface $reasonRepository
     */
    public function __construct(
        Context $context,
        ReasonRepositoryInterface $reasonRepository
    ) {
        $this->context = $context;
        $this->reasonRepository = $reasonRepository;
    }

    /**
     * Return Reason ID
     *
     * @return int|null
     */
    public function getReasonId()
    {
        try {
            return $this->reasonRepository->getById(
                $this->context->getRequest()->getParam('reason_id')
            )->getId();
        } catch (NoSuchEntityException $e) {
        }
        return null;
    }
    /**
     * Return Reason Is Main
     *
     * @return int|null
     */
    public function getIsMain()
    {
        try {
            return $this->reasonRepository->getById(
                $this->context->getRequest()->getParam('reason_id')
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
