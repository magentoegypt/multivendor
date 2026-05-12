<?php
namespace MagentoEgypt\SetExtend\Plugin\Price\Local;

class Format
{
    /**
     * Arab locale code
     */
    private const ARABIC_CODES = [
        'SAR',
        'EGP'
    ];

    /**
     * @var \Magento\Framework\App\ScopeResolverInterface
     */
    protected $_scopeResolver;

    /**
     * @param \Magento\Framework\App\ScopeResolverInterface $scopeResolver
     */
    public function __construct(
        \Magento\Framework\App\ScopeResolverInterface $scopeResolver
    ) {
        $this->_scopeResolver = $scopeResolver;
    }

    /**
     * {@inheritdoc}
     *
     * @param $subject
     * @param $result
     *
     * @return mixed
     */
    public function afterGetPriceFormat($subject, $result, $localeCode = null, $currencyCode = null)
    {
        if ($currencyCode === null) {
            $currencyCode = $this->_scopeResolver->getScope()->getCurrentCurrency()->getCode();
        }
        $precision = 0;
        $result['precision'] = $precision;
        $result['requiredPrecision'] = $precision;
        if (in_array($currencyCode, self::ARABIC_CODES)) {
            $result['groupSymbol'] = ',';
            $result['decimalSymbol'] = '.';
        }
        return $result;
    }
}
