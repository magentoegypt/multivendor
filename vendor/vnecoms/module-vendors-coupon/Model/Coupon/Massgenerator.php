<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCoupon\Model\Coupon;

/**
 * Vendor Coupon Mass Coupon Generator
 */
class Massgenerator extends \Magento\Framework\DataObject
{
    /**
     * Maximum probability of guessing the coupon on the first attempt
     */
    const MAX_PROBABILITY_OF_GUESSING = 0.25;

    /**
     * Number of attempts to generate
     */
    const MAX_GENERATE_ATTEMPTS = 10;

    /**
     * Count of generated Coupons
     * @var int
     */
    protected $generatedCount = 0;

    /**
     * @var array
     */
    protected $generatedCodes = [];

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;

    /**
     * @var \Vnecoms\VendorsCoupon\Model\CouponFactory
     */
    protected $couponFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $dateTime;

    /**
     * @var \Vnecoms\VendorsCoupon\Helper\Data
     */
    protected $_couponHelper;

    /**
     * @var \Vnecoms\VendorsCoupon\Model\ResourceModel\Coupon
     */
    protected $_couponResource;

    /**
     *
     * @param \Vnecoms\VendorsCoupon\Helper\Data $couponHelper
     * @param \Vnecoms\VendorsCoupon\Model\ResourceModel\Coupon $couponResource
     * @param \Vnecoms\VendorsCoupon\Model\CouponFactory $couponFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     */
    public function __construct(
        \Vnecoms\VendorsCoupon\Helper\Data $couponHelper,
        \Vnecoms\VendorsCoupon\Model\ResourceModel\Coupon $couponResource,
        \Vnecoms\VendorsCoupon\Model\CouponFactory $couponFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Stdlib\DateTime $dateTime
    ) {
        $this->_couponHelper = $couponHelper;
        $this->_couponResource = $couponResource;
        $this->date = $date;
        $this->couponFactory = $couponFactory;
        $this->dateTime = $dateTime;
    }
    /**
     * Generate coupon code
     *
     * @return string
     */
    public function generateCode()
    {
        $format = $this->getFormat();
        if (empty($format)) {
            $format = \Magento\SalesRule\Helper\Coupon::COUPON_FORMAT_ALPHANUMERIC;
        }

        $splitChar = $this->getDelimiter();
        $charset = $this->_couponHelper->getCharset();

        $code = '';
        $charsetSize = count($charset);
        $split = max(0, (int)$this->_couponHelper->getCouponDash());
        $length = max(1, (int)$this->_couponHelper->getCouponLength());
        for ($i = 0; $i < $length; ++$i) {
            $char = $charset[\Magento\Framework\Math\Random::getRandomNumber(0, $charsetSize - 1)];
            if (($split > 0) && (($i % $split) === 0) && ($i !== 0)) {
                $char = $splitChar . $char;
            }
            $code .= $char;
        }

        return ($this->_couponHelper->getCouponPrefix()?$this->_couponHelper->getCouponPrefix().$this->getDelimiter():'').
            ($this->getCodePrefix()?$this->getCodePrefix().$this->getDelimiter():'').
            $code .
            ($this->_couponHelper->getCouponSuffix()?$this->getDelimiter().$this->_couponHelper->getCouponSuffix():'');
    }

    /**
     * Retrieve delimiter
     *
     * @return string
     */
    public function getDelimiter()
    {
        return $this->_couponHelper->getCodeSeparator();
    }

    /**
     * Generate Coupons Pool
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return $this
     */
    public function generatePool()
    {
        $this->generatedCount = 0;
        $this->generatedCodes = [];
        $size = $this->getQty();
        $maxAttempts = $this->getMaxAttempts() ? $this->getMaxAttempts() : self::MAX_GENERATE_ATTEMPTS;
        $this->increaseLength();
        /** @var $coupon \Vnecoms\VendorsCoupon\Model\Coupon */
        $coupon = $this->couponFactory->create();
        $nowTimestamp = $this->dateTime->formatDate($this->date->gmtTimestamp());

        for ($i = 0; $i < $size; $i++) {
            $attempt = 0;
            do {
                if ($attempt >= $maxAttempts) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('We cannot create the requested Coupon Qty. Please check your settings and try again.')
                    );
                }
                $code = $this->generateCode();
                ++$attempt;
            } while ($this->getCouponResource()->exists($code));

            $fromDate = $this->getFromDate();
            if ($fromDate instanceof \DateTime) {
                $fromDate = $fromDate->format('Y-m-d');
            }

            $toDate = $this->getToDate();
            if ($toDate instanceof \DateTime) {
                $toDate = $toDate->format('Y-m-d');
            }

            $coupon->setId(null)
                ->setVendorId($this->getVendorId())
                ->setUsageLimit($this->getUsageLimit())
                ->setFromDate($fromDate)
                ->setAction($this->getAction())
                ->setBuyX($this->getBuyX())
                ->setToDate($toDate)
                ->setCreatedAt($nowTimestamp)
                ->setAmount($this->getCouponValue())
                ->setCode($code)
                ->save();

            $this->generatedCount += 1;
            $this->generatedCodes[] = $code;
        }

        return $this;
    }

    /**
     * Increase the length of Code if probability is low
     *
     * @return void
     */
    protected function increaseLength()
    {
        $maxProbability = $this->getMaxProbability() ? $this->getMaxProbability() : self::MAX_PROBABILITY_OF_GUESSING;
        $chars = count($this->_couponHelper->getCharset());
        $size = $this->getQty();
        $length = (int)$this->getLength();
        $maxCodes = pow($chars, $length);
        $probability = $size / $maxCodes;

        if ($probability > $maxProbability) {
            do {
                $length++;
                $maxCodes = pow($chars, $length);
                $probability = $size / $maxCodes;
            } while ($probability > $maxProbability);
            $this->setLength($length);
        }
    }

    /**
     * Validate data input
     *
     * @param array $data
     * @return bool
     */
    public function validateData($data)
    {
        return !empty($data)
        && !empty($data['qty'])
        && (int)$data['qty'] > 0
        && (int)$data['coupon_value'] >= 0;
    }

    /**
     * Return the generated coupon codes
     *
     * @return array
     */
    public function getGeneratedCodes()
    {
        return $this->generatedCodes;
    }

    /**
     * Retrieve count of generated Coupons
     *
     * @return int
     */
    public function getGeneratedCount()
    {
        return $this->generatedCount;
    }

    /**
     * Get Coupon Resource
     *
     * @return \Vnecoms\VendorsCoupon\Model\ResourceModel\Coupon
     */
    public function getCouponResource(){
        return $this->_couponResource;
    }
}
