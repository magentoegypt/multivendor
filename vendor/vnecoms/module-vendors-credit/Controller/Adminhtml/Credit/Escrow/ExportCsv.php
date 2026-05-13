<?php

namespace Vnecoms\VendorsCredit\Controller\Adminhtml\Credit\Escrow;

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class ExportCsv.
 *
 * @author Vnecoms team <vnecoms.com>
 */
class ExportCsv extends \Vnecoms\VendorsCredit\Controller\Adminhtml\Credit\Export
{
    /**
     * @return mixed
     */
    public function execute()
    {
        $this->initVendor();
        $fileName = 'escrow_transaction.csv';
        $content = $this->_view->getLayout()->createBlock('Vnecoms\VendorsCredit\Block\Adminhtml\Vendor\Edit\Tab\Escrow\Grid')->getCsv();

        return $this->_fileFactory->create($fileName, $content, DirectoryList::VAR_DIR);
    }
}
