<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Widget Instance edit form.
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */

namespace Vnecoms\VendorsCms\Block\Vendors\App\Edit;

class Form extends \Vnecoms\Vendors\Block\Vendors\Widget\Form\Generic
{
    /**
     * Prepare form before rendering HTML.
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post']]
        );
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
