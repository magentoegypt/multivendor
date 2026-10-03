<?php
declare(strict_types=1);
namespace MagentoEgypt\Fulfillment\Controller\Quote;

class Index implements \Magento\Framework\App\Action\HttpGetActionInterface
{
    public function __construct(
        private \Magento\Framework\App\RequestInterface $request,
        private \Magento\Framework\Controller\Result\JsonFactory $json,
        private \MagentoEgypt\Fulfillment\Model\Preview $preview,
        private \Psr\Log\LoggerInterface $logger
    ) {}

    public function execute()
    {
        $result = $this->json->create()->setHeader('Cache-Control', 'private, no-store', true);
        try {
            $raw = $this->request->getParam('items', '[]');
            if (!is_string($raw) || strlen($raw) > 16000) throw new \InvalidArgumentException('Invalid product list.');
            $items = json_decode($raw, true, 8, JSON_THROW_ON_ERROR);
            if (!is_array($items)) throw new \InvalidArgumentException('Invalid product list.');
            foreach (['country', 'strategy'] as $key) {
                if (!is_string($this->request->getParam($key, ''))) throw new \InvalidArgumentException('Invalid destination or strategy.');
            }
            $ids = [];
            foreach (['region', 'city', 'locality'] as $key) {
                $value = $this->request->getParam($key, '0');
                if (!is_scalar($value) || !preg_match('/^\d{1,10}$/D', (string)$value) || (int)$value > 2147483647) throw new \InvalidArgumentException('Invalid location identifier.');
                $ids[$key] = (int)$value;
            }
            return $result->setData($this->preview->execute($items, $this->request->getParam('country', ''), $ids['region'],
                $ids['city'], $ids['locality'], $this->request->getParam('strategy', 'direct')));
        } catch (\JsonException | \InvalidArgumentException $e) {
            return $result->setHttpResponseCode(400)->setData(['error'=>'Invalid fulfillment request.']);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return $result->setHttpResponseCode(400)->setData(['error'=>'Product or destination is unavailable.']);
        } catch (\Throwable $e) {
            $this->logger->error('Fulfillment preview failed', ['exception'=>$e]);
            return $result->setHttpResponseCode(503)->setData(['error'=>'Fulfillment preview is temporarily unavailable.']);
        }
    }
}
