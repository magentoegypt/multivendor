<?php
namespace MagentoEgypt\SetExtend\Plugin;

class DirectoryCurrency
{
    /**
     * Around plugin for getOutputFormat method
     *
     * @param \Magento\Directory\Model\Currency $subject
     * @param \Closure $proceed
     * @param mixed ...$args
     * @return mixed
     */
    public function aroundGetOutputFormat(
        \Magento\Directory\Model\Currency $subject,
        \Closure $proceed
    ) {
        $formatted = $subject->formatTxt(0);
        $number = $subject->formatTxt(0, ['display' => \Magento\Framework\Currency::NO_SYMBOL]);
        $number = floatval($number);
        return str_replace($this->trimUnicodeDirectionMark($number), '%s', $formatted);
    }

    /**
     * This method removes LRM and RLM marks from string
     *
     * @param string $string
     * @return $this
     */
    private function trimUnicodeDirectionMark($string)
    {
        if (preg_match('/^(\x{200E}|\x{200F})/u', $string, $match)) {
            $string = preg_replace('/^'.$match[1].'/u', '', $string);
        }
        return $string;
    }
}
