<?php
namespace MagentoEgypt\SetExtend\Plugin\Price;

class NumberFormatter extends \Magento\Framework\NumberFormatter
{
	const BAW_FIXED_LOCALE = 'de_DE';
	protected $_localeCode;
    protected $_currencySymbol = [];

	public function __construct(
        $locale = null,
        $style = \Magento\Framework\NumberFormatter::CURRENCY,
        $pattern = null
    ) {
    	$this->_localeCode = $locale;
    	if(strpos($locale, 'ar_') !== false) $locale = self::BAW_FIXED_LOCALE;
        parent::__construct($locale, $style, $pattern);
    }

	public function formatCurrency(float $amount, string $currency): string|false
	{
		$this->setAttribute(\Magento\Framework\NumberFormatter::FRACTION_DIGITS, 0);
		if($this->getLocale() == self::BAW_FIXED_LOCALE && $currency != 'USD') {
            return str_replace([$currency,'.'],[$this->getSymbolExtend($currency),','],parent::formatCurrency($amount, $currency));
		}
		return parent::formatCurrency($amount, $currency);
	}

	public function getSymbolExtend(string $currency): string|false
	{
		if(!isset($this->_currencySymbol[$currency])) {

            $localeCode = explode('@',$this->_localeCode);
            $formatter = new \NumberFormatter($localeCode[0], NumberFormatter::CURRENCY);
            $formatter->setTextAttribute(\NumberFormatter::CURRENCY_CODE, $currency);
			$this->_currencySymbol[$currency] = $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
		}
		return $this->_currencySymbol[$currency];
	}
}
