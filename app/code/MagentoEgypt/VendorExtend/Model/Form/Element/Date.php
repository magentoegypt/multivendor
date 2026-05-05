<?php
namespace MagentoEgypt\VendorExtend\Model\Form\Element;

class Date extends \Magento\Framework\Data\Form\Element\Date
{
    /**
     * Get date value as string.
     *
     * Format can be specified, or it will be taken from $this->getFormat()
     *
     * @param string $format (compatible with \DateTime)
     * @return string
     */
    public function getValue($format = null)
    {
        if (empty($this->_value)) {
            return '';
        }
        if (null === $format) {
            $format = $this->getDateFormat();
            $format .= ($format && $this->getTimeFormat()) ? ' ' : '';
            $format .= $this->getTimeFormat() ? $this->getTimeFormat() : '';
        }
        return $this->localeDate->formatDateTime(
            $this->_value,
            null,
            null,
            'en_US',
            $this->_value->getTimezone(),
            $format
        );
    }
}
