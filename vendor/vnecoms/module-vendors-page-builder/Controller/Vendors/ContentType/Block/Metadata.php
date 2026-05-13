<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Vnecoms\VendorsPageBuilder\Controller\Vendors\ContentType\Block;

use Magento\Framework\Controller\ResultFactory;
use Vnecoms\Vendors\App\Action\Context;
use Vnecoms\Vendors\Controller\Vendors\Action;

class Metadata extends Action
{

    /**
     * @var \Vncoms\VendorsCms\Model\ResourceModel\Block\CollectionFactory 
     */
    private $blockCollectionFactory;

    /**
     * Metadata constructor.
     * @param Context $context
     * @param \Vnecoms\VendorsCms\Model\ResourceModel\Block\CollectionFactory $blockCollectionFactory
     */
    public function __construct(
        Context $context,
        \Vnecoms\VendorsCms\Model\ResourceModel\Block\CollectionFactory $blockCollectionFactory
    ) {
        parent::__construct($context);

        $this->blockCollectionFactory = $blockCollectionFactory;
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        try {
            $collection = $this->blockCollectionFactory->create();
            $blocks = $collection
                ->addFieldToSelect(['title','is_active'])
                ->addFieldToFilter('block_id', ['eq' => $params['block_id']])
                ->load();
            $result = $blocks->getFirstItem()->toArray();
        } catch (\Exception $e) {
            $result = [
                'error' => $e->getMessage(),
                'errorcode' => $e->getCode()
            ];
        }
        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }
}
