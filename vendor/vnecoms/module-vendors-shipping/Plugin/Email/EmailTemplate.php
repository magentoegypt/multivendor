<?php
declare(strict_types=1);

namespace Vnecoms\VendorsShipping\Plugin\Email;

class EmailTemplate
{
    /**
     * @param \Magento\Email\Model\Template $subject
     * @return array
     */
    public function beforeBeforeSave(\Magento\Email\Model\Template $subject)
    {
        $subject->setData('is_legacy', 1);
        return [];
    }
}