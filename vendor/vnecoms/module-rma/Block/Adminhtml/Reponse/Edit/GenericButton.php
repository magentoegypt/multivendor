<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Block\Adminhtml\Reponse\Edit;

use Magento\Backend\Block\Widget\Context;
use Vnecoms\RMA\Api\ReponseRepositoryInterface;
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
     * @var ReponseRepositoryInterface
     */
    protected $reponseRepository;

    /**
     * GenericButton constructor.
     * @param Context $context
     * @param ReponseRepositoryInterface $reponseRepository
     */
    public function __construct(
        Context $context,
        ReponseRepositoryInterface $reponseRepository
    ) {
        $this->context = $context;
        $this->reponseRepository = $reponseRepository;
    }

    /**
     * Return Reponse ID
     *
     * @return int|null
     */
    public function getReponseId()
    {
        try {
            return $this->reponseRepository->getById(
                $this->context->getRequest()->getParam('reponse_id')
            )->getId();
        } catch (NoSuchEntityException $e) {
        }
        return null;
    }
    /**
     * Return Reponse Is Main
     *
     * @return int|null
     */
    public function getIsMain()
    {
        try {
            return $this->reponseRepository->getById(
                $this->context->getRequest()->getParam('reponse_id')
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
