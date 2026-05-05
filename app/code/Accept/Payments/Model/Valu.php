<?php
namespace Accept\Payments\Model;

use Magento\Payment\Model\Method\AbstractMethod;

class Valu extends AbstractMethod
{
    const CODE = "valu";

    protected $_code = self::CODE;
    protected $_isGateway = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = false;
    protected $_canRefundInvoicePartial = false;
}
