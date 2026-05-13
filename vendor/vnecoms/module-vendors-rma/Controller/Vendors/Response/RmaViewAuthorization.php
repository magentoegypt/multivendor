<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsRMA\Controller\Vendors\Response;

class RmaViewAuthorization implements RmaViewAuthorizationInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_Vendors::rma_response';
    /**
     * @var \Vnecoms\Vendors\Model\Session
     */
    protected $vendorSession;

    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    protected $orderConfig;

    /**
     * @param \Vnecoms\Vendors\Model\Session $vendorSession
     */
    public function __construct(
        \Vnecoms\Vendors\Model\Session $vendorSession
    ) {
        $this->vendorSession = $vendorSession;
    }

    /**
     * {@inheritdoc}
     */
    public function canView(\Vnecoms\RMA\Model\Reponse $request)
    {
        $vendor = $this->vendorSession->getVendor();
        if ($request->getId()
            && $request->getVendorId()
            && $request->getVendorId() == $vendor->getId()
        ) {
            return true;
        }
        return false;
    }

}
