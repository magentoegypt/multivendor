<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Html\Select;

/**
 * A select column for a config dynamic-rows field.
 *
 * AbstractFieldArray renders a column renderer with setInputName() /
 * setInputId(); a plain Html\Select would store those as unrelated data and
 * render a nameless <select>, so the value would never be posted.
 */
class OptionsSelect extends Select
{
    /**
     * @return $this
     */
    public function setInputName(string $value): self
    {
        return $this->setName($value);
    }

    /**
     * @return $this
     */
    public function setInputId(string $value): self
    {
        return $this->setId($value);
    }
}
