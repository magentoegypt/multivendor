<?php declare(strict_types=1);

namespace Mstore\Checkout\Model\Payment;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Model\CcConfig;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Magento\Quote\Model\QuoteRepository;
use Mstore\Checkout\Model\ConfigProviderInterface;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var PaymentMethodManagementInterface
     */
    protected $paymentMethodManagement;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var CcConfig
     */
    private $ccConfig;

    /**
     * ConfigProvider constructor.
     * @param QuoteRepository $quoteRepository
     * @param PaymentMethodManagementInterface $paymentMethodManagement
     * @param ScopeConfigInterface $scopeConfig
     * @param CcConfig $ccConfig
     */
    public function __construct(
        QuoteRepository $quoteRepository,
        PaymentMethodManagementInterface $paymentMethodManagement,
        ScopeConfigInterface $scopeConfig,
        CcConfig $ccConfig
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->paymentMethodManagement = $paymentMethodManagement;
        $this->scopeConfig = $scopeConfig;
        $this->ccConfig = $ccConfig;
    }


    /**
     * @inheritDoc
     */
    public function getConfig($cartId = null)
    {
        if (null === $cartId) {
            return ['payment' => []];
        }
        $paymentMethods = [];
        try {
            $quote = $this->quoteRepository->getActive($cartId);
            foreach ($this->paymentMethodManagement->getList($quote->getId()) as $paymentMethod) {
                $code = $paymentMethod->getCode();
                $paymentMethods[$code] = [
                    'code' => $code,
                    'title' => $paymentMethod->getTitle(),
                    'instructions' => $paymentMethod->getConfigData('instructions')
                ];
            }
        } catch (NoSuchEntityException $noSuchEntityException) {
            return ['payment' => []];
        }

        return ['payment' => $paymentMethods];
    }
}
