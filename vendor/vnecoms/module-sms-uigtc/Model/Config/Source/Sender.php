<?php
namespace Vnecoms\SmsUigtc\Model\Config\Source;

use Magento\Framework\App\ObjectManager;

class Sender extends \Magento\Framework\DataObject implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Vnecoms\SmsUigtc\Model\Uigtc
     */
    protected $gateway;

    /**
     * Sender constructor.
     * @param \Vnecoms\SmsUigtc\Model\Uigtc $gateway
     * @param array $data
     */
    public function __construct(
        \Vnecoms\SmsUigtc\Model\Uigtc $gateway,
        array $data = []
    ) {
        $this->gateway = $gateway;
        parent::__construct($data);
    }

    /**
     * Generate list of email templates
     *
     * @return array
     */
    public function toOptionArray()
    {
        return $this->gateway->getSenderIds();
    }
}
