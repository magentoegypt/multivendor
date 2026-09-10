<?php
declare(strict_types=1);

namespace MagentoEgypt\CheckoutExtend\Model\Pdf\Items\Creditmemo;

use Magento\Framework\App\ObjectManager;
use MagentoEgypt\CheckoutExtend\Model\Pdf\ArabicText;
use Magento\Sales\Model\Order\Pdf\Items\Creditmemo\DefaultCreditmemo;

/**
 * The credit memo's item row, with Arabic that joins and reads the right way.
 *
 * Like the packing slip and unlike the invoice, core added no RTL handling
 * here at all: the name goes into the page as `html_entity_decode(...)`, so
 * Arabic was drawn in isolated letters running left to right.
 *
 * See MagentoEgypt\CheckoutExtend\Model\Pdf\Items\Invoice\Arabic for why the
 * font needed no changing, and why this is a pdf.xml renderer rather than a
 * plugin.
 *
 * `draw()` is forked from core to change the calls that carry text. Keep it in
 * sync on upgrade — diff against
 * vendor/magento/module-sales/Model/Order/Pdf/Items/Creditmemo/DefaultCreditmemo.php
 */
class Arabic extends DefaultCreditmemo
{
    private ?ArabicText $arabicText = null;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ?\Magento\Framework\Model\Context $context = null,
        ?\Magento\Framework\Registry $registry = null,
        ?\Magento\Tax\Helper\Data $taxData = null,
        ?\Magento\Framework\Filesystem $filesystem = null,
        ?\Magento\Framework\Filter\FilterManager $filterManager = null,
        ?\Magento\Framework\Stdlib\StringUtils $string = null,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        ?ArabicText $arabicText = null
    ) {
        $om = ObjectManager::getInstance();
        $this->arabicText = $arabicText ?: $om->get(ArabicText::class);

        parent::__construct(
            $context ?: $om->get(\Magento\Framework\Model\Context::class),
            $registry ?: $om->get(\Magento\Framework\Registry::class),
            $taxData ?: $om->get(\Magento\Tax\Helper\Data::class),
            $filesystem ?: $om->get(\Magento\Framework\Filesystem::class),
            $filterManager ?: $om->get(\Magento\Framework\Filter\FilterManager::class),
            $string ?: $om->get(\Magento\Framework\Stdlib\StringUtils::class),
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Forked from core; only the text-bearing calls change.
     */
    public function draw()
    {
        $order = $this->getOrder();
        $item = $this->getItem();
        $pdf = $this->getPdf();
        $page = $this->getPage();
        $lines = [];

        // draw Product name
        $lines[0] = [
            [
                'text' => $this->string->split($this->hmPrepareText((string)$item->getName()), 35, true, true),
                'feed' => 35
            ]
        ];

        // draw SKU
        $lines[0][] = [
            'text' => $this->string->split($this->hmPrepareText((string)$this->getSku($item)), 17),
            'feed' => 255,
            'align' => 'right',
        ];

        // draw Total (ex)
        $lines[0][] = [
            'text' => $order->formatPriceTxt($item->getRowTotal()),
            'feed' => 330,
            'font' => 'bold',
            'align' => 'right',
        ];

        // draw Discount
        $lines[0][] = [
            'text' => $order->formatPriceTxt(-$item->getDiscountAmount()),
            'feed' => 380,
            'font' => 'bold',
            'align' => 'right',
        ];

        // draw QTY
        $lines[0][] = ['text' => $item->getQty() * 1, 'feed' => 445, 'font' => 'bold', 'align' => 'right'];

        // draw Tax
        $lines[0][] = [
            'text' => $order->formatPriceTxt($item->getTaxAmount()),
            'feed' => 495,
            'font' => 'bold',
            'align' => 'right',
        ];

        // draw Total (inc)
        $subtotal = $item->getRowTotal() +
            $item->getTaxAmount() +
            $item->getDiscountTaxCompensationAmount() -
            $item->getDiscountAmount();
        $lines[0][] = [
            'text' => $order->formatPriceTxt($subtotal),
            'feed' => 565,
            'font' => 'bold',
            'align' => 'right',
        ];

        // draw options
        $options = $this->getItemOptions();
        if ($options) {
            foreach ($options as $option) {
                $lines[][] = [
                    'text' => $this->string->split(
                        $this->hmPrepareText($this->filterManager->stripTags($option['label'])),
                        40,
                        true,
                        true
                    ),
                    'font' => 'italic',
                    'feed' => 35,
                ];

                $printValue = $option['print_value']
                    ?? $this->filterManager->stripTags($option['value']);

                $values = explode(PHP_EOL, (string)$printValue);
                $text = [];
                foreach ($values as $value) {
                    foreach ($this->string->split($this->hmPrepareText($value), 50, true, true) as $subValue) {
                        $text[] = $subValue;
                    }
                }

                $lines[][] = ['text' => $text, 'feed' => 40];
            }
        }

        $lineBlock = ['lines' => $lines, 'height' => 20, 'shift' => 5];

        $page = $pdf->drawLineBlocks($page, [$lineBlock], ['table_header' => true]);
        $this->setPage($page);
    }

    private function hmPrepareText(string $string): string
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        return $this->arabicText->render(html_entity_decode($string));
    }
}
