<?php

namespace Vnecoms\VendorsCredit\Controller\Adminhtml\Credit\Escrow;

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class ExportXml.
 *
 * @author Vnecoms team <vnecoms.com>
 */
class ExportXml extends \Vnecoms\VendorsCredit\Controller\Adminhtml\Credit\Export
{
    public function execute()
    {
        $this->initVendor();
        $fileName = 'escrow_transaction.xml';
        $content = $this->_view->getLayout()->createBlock('Vnecoms\VendorsCredit\Block\Adminhtml\Vendor\Edit\Tab\Escrow\Grid')->getXml();

        return $this->_fileFactory->create($fileName, $content, DirectoryList::VAR_DIR);
    }
}
