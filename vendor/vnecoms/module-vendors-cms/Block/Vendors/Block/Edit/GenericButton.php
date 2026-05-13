<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Block\Vendors\Block\Edit;

use Magento\Backend\Block\Widget\Context;
use Vnecoms\VendorsCms\Api\BlockRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class GenericButton.
 */
class GenericButton
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var BlockRepositoryInterface
     */
    protected $blockRepository;

    /**
     * @param Context                 $context
     * @param BlockRepositoryInterface $pageRepository
     */
    public function __construct(
        Context $context,
        BlockRepositoryInterface $pageRepository
    ) {
        $this->context = $context;
        $this->blockRepository = $pageRepository;
    }

    /**
     * @return int|void|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getBlockId()
    {
        try {
            return $this->blockRepository->getById(
                $this->context->getRequest()->getParam('block_id')
            )->getId();
        } catch (NoSuchEntityException $e) {
        }

        return;
    }

    /**
     * Generate url by route and parameters.
     *
     * @param string $route
     * @param array  $params
     *
     * @return string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
