<?php
namespace Accept\Payments\Model;

use Magento\Payment\Model\Method\AbstractMethod;

class Shahry extends AbstractMethod
{
    const CODE = "shahry";

    protected $_code = self::CODE;
    protected $_isGateway = true;
    protected $_canCapture = false;
    protected $_canCapturePartial = false;
    protected $_canRefund = false;
    protected $_canRefundInvoicePartial = false;
}
