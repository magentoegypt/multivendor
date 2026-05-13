<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Block\Adminhtml\Request\Edit;

use Magento\Backend\Block\Widget\Context;
use Vnecoms\RMA\Api\RequestRepositoryInterface;
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
     * @var RequestRepositoryInterface
     */
    protected $requestRepository;

    /**
     * @param Context $context
     * @param RequestRepositoryInterface $requestRepository
     */
    public function __construct(
        Context $context,
        RequestRepositoryInterface $requestRepository
    ) {
        $this->context = $context;
        $this->requestRepository = $requestRepository;
    }

    /**
     * Return Template ID
     *
     * @return int|null
     */
    public function getTicketId()
    {
        try {
            return $this->requestRepository->getById(
                $this->context->getRequest()->getParam('request_id')
            )->getId();
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
