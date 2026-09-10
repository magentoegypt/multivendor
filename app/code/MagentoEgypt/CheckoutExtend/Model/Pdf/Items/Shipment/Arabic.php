<?php
declare(strict_types=1);

namespace MagentoEgypt\CheckoutExtend\Model\Pdf\Items\Shipment;

use Magento\Framework\App\ObjectManager;
use MagentoEgypt\CheckoutExtend\Model\Pdf\ArabicText;
use Magento\Sales\Model\Order\Pdf\Items\Shipment\DefaultShipment;

/**
 * The packing slip's item row, with Arabic that joins and reads the right way.
 *
 * WORSE OFF THAN THE INVOICE, not better. 2.4.8 added its RtlTextHandler to the
 * invoice renderer only — this one draws `html_entity_decode($item->getName())`
 * straight into the page. So Arabic here was not merely unshaped, it was not
 * even reversed: isolated letters running left to right.
 *
 * See MagentoEgypt\CheckoutExtend\Model\Pdf\Items\Invoice\Arabic for why the
 * font needed no changing, and why this is a pdf.xml renderer rather than a
 * plugin.
 *
 * `draw()` is forked from core to change the two calls that carry text. Keep it
 * in sync on upgrade — diff against
 * vendor/magento/module-sales/Model/Order/Pdf/Items/Shipment/DefaultShipment.php
 */
class Arabic extends DefaultShipment
{
    private ?ArabicText $arabicText = null;

    /**
     * Optional throughout: the compiled DI config has never seen this class and
     * its factory calls the constructor with nothing.
     *
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
     * Forked from core; the name, SKU and option text go through the shaper.
     */
    public function draw()
    {
        $item = $this->getItem();
        $pdf = $this->getPdf();
        $page = $this->getPage();
        $lines = [];

        // draw Product name
        $lines[0] = [
            [
                'text' => $this->string->split($this->hmPrepareText((string)$item->getName()), 60, true, true),
                'feed' => 100
            ]
        ];

        // draw QTY
        $lines[0][] = ['text' => $item->getQty() * 1, 'feed' => 35];

        // draw SKU
        $lines[0][] = [
            'text' => $this->string->split($this->hmPrepareText((string)$this->getSku($item)), 25),
            'feed' => 565,
            'align' => 'right',
        ];

        // Custom options
        $options = $this->getItemOptions();
        if ($options) {
            foreach ($options as $option) {
                $lines[][] = [
                    'text' => $this->string->split(
                        $this->hmPrepareText($this->filterManager->stripTags($option['label'])),
                        70,
                        true,
                        true
                    ),
                    'font' => 'italic',
                    'feed' => 110,
                ];

                if ($option['value'] !== null) {
                    $printValue = $option['print_value']
                        ?? $this->filterManager->stripTags($option['value']);
                    $printValue = str_replace(PHP_EOL, ', ', $printValue);
                    $values = explode(', ', $printValue);
                    $text = [];
                    foreach ($values as $value) {
                        foreach ($this->string->split($this->hmPrepareText($value), 50, true, true) as $subValue) {
                            $text[] = $subValue;
                        }
                    }

                    $lines[][] = ['text' => $text, 'feed' => 115];
                }
            }
        }

        $lineBlock = ['lines' => $lines, 'height' => 20, 'shift' => 5];

        $page = $pdf->drawLineBlocks($page, [$lineBlock], ['table_header' => true]);
        $this->setPage($page);
    }

    /**
     * Latin comes back byte-identical — the shaper returns untouched anything
     * that holds no Arabic.
     */
    private function hmPrepareText(string $string): string
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        return $this->arabicText->render(html_entity_decode($string));
    }
}
