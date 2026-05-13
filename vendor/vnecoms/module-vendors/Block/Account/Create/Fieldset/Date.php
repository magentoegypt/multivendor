<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\Vendors\Block\Account\Create\Fieldset;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Framework\Api\ArrayObjectSearch;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Locale\Bundle\DataBundle;
use Magento\Framework\Locale\ResolverInterface;

/**
 * Customer date of birth attribute block
 *
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Date extends Field
{
    /**
     * Constants for borders of date-type customer attributes
     */
    const MIN_DATE_RANGE_KEY = 'date_range_min';

    const MAX_DATE_RANGE_KEY = 'date_range_max';

    /**
     * @var array
     */
    protected $_dateInputs = [];

    /**
     * @var \Magento\Framework\View\Element\Html\Date
     */
    protected $dateElement;

    /**
     * @var \Magento\Framework\Data\Form\FilterFactory
     */
    protected $filterFactory;

    /**
     * JSON Encoder
     *
     * @var EncoderInterface
     */
    private $encoder;

    /**
     * @var ResolverInterface
     */
    private $localeResolver;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * Dob constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Helper\Address $addressHelper
     * @param CustomerMetadataInterface $customerMetadata
     * @param \Magento\Framework\View\Element\Html\Date $dateElement
     * @param \Magento\Framework\Data\Form\FilterFactory $filterFactory
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     * @param EncoderInterface|null $encoder
     * @param ResolverInterface|null $localeResolver
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Helper\Address $addressHelper,
        CustomerMetadataInterface $customerMetadata,
        \Magento\Framework\View\Element\Html\Date $dateElement,
        \Magento\Framework\Data\Form\FilterFactory $filterFactory,
        \Magento\Framework\Registry $registry,
        array $data = [],
        ?EncoderInterface $encoder = null,
        ?ResolverInterface $localeResolver = null
    ) {
        $this->dateElement = $dateElement;
        $this->filterFactory = $filterFactory;
        $this->encoder = $encoder ?? ObjectManager::getInstance()->get(EncoderInterface::class);
        $this->localeResolver = $localeResolver ?? ObjectManager::getInstance()->get(ResolverInterface::class);
        parent::__construct($context, $registry, $data);
    }

    /**
     * Create correct date field
     *
     * @return string
     */
    public function getFieldHtml()
    {
        $this->dateElement->setData(
            [
                'extra_params' => $this->getHtmlExtraParams(),
                'name' => $this->getFieldName(),
                'id' => $this->getFieldId(),
                'class' => $this->getFrontendClass(),
                'value' => $this->getFieldValue(),
                'date_format' => $this->getDateFormat(),
                'image' => $this->getViewFileUrl('Magento_Theme::calendar.png'),
                'years_range' => '-120y:c+nn',
                'change_month' => 'true',
                'change_year' => 'true',
                'show_on' => 'both',
                'first_day' => $this->getFirstDay()
            ]
        );
        return $this->dateElement->getHtml();
    }


    /**
     * Return data-validate rules
     *
     * @return string
     */
    public function getHtmlExtraParams()
    {
        $validators = [];
        if ($this->isAttributeRequired()) {
            $validators['required'] = true;
        }
        $validators['validate-date'] = [
            'dateFormat' => $this->getDateFormat()
        ];
        return 'data-validate="' . $this->_escaper->escapeHtml(json_encode($validators)) . '"';
    }

    /**
     * Returns format which will be applied for DOB in javascript
     *
     * @return string
     */
    public function getDateFormat()
    {
        return 'Y-m-d';
    }

    /**
     * Return first day of the week
     *
     * @return int
     */
    public function getFirstDay()
    {
        return (int)$this->_scopeConfig->getValue(
            'general/locale/firstday',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get translated calendar config json formatted
     *
     * @return string
     */
    public function getTranslatedCalendarConfigJson(): string
    {
        $localeData = (new DataBundle())->get($this->localeResolver->getLocale());
        $monthsData = $localeData['calendar']['gregorian']['monthNames'];
        $daysData = $localeData['calendar']['gregorian']['dayNames'];

        return $this->encoder->encode(
            [
                'closeText' => __('Done'),
                'prevText' => __('Prev'),
                'nextText' => __('Next'),
                'currentText' => __('Today'),
                'monthNames' => array_values(iterator_to_array($monthsData['format']['wide'])),
                'monthNamesShort' => array_values(iterator_to_array($monthsData['format']['abbreviated'])),
                'dayNames' => array_values(iterator_to_array($daysData['format']['wide'])),
                'dayNamesShort' => array_values(iterator_to_array($daysData['format']['abbreviated'])),
                'dayNamesMin' => array_values(iterator_to_array(($daysData['format']['short']) ?: $daysData['format']['abbreviated'])),
            ]
        );
    }

    /**
     * Set 2 places for day value in format string
     *
     * @param string $format
     * @return string
     */
    private function setTwoDayPlaces(string $format): string
    {
        return preg_replace(
            '/(?<!d)d(?!d)/',
            'dd',
            $format
        );
    }
}
