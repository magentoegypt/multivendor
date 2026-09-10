<?php
declare(strict_types=1);

namespace MagentoEgypt\CheckoutExtend\Model\Pdf\Items\Invoice;

use Magento\Framework\App\ObjectManager;
use MagentoEgypt\CheckoutExtend\Model\Pdf\ArabicText;
use Magento\Sales\Model\Order\Pdf\Items\Invoice\DefaultInvoice;

/**
 * The invoice's item row, with Arabic that joins and reads the right way
 * (CL036-DEV01.23).
 *
 * WHAT CORE ALREADY DOES, AND WHY IT IS NOT ENOUGH.
 *
 * 2.4.8 does know the text is right-to-left: DefaultInvoice::prepareText() runs
 * every product name through Sales\Model\RtlTextHandler. But that class only
 * REVERSES — it flips word order and the characters inside each word. It never
 * SHAPES. Arabic letters change form depending on their neighbours, and without
 * that step every letter is drawn in its isolated form. That is the
 * "disconnected" half of the report, and reversing without shaping is why it
 * also looks wrong end-first.
 *
 * So this renderer replaces prepareText's transform rather than adding to it —
 * doing both would reverse the string twice. ArabicText shapes AND puts the
 * glyphs in visual order, because the two steps have to agree about direction.
 *
 * The font needed no changing. The ticket asked for Amiri or Cairo to be
 * embedded; Magento already draws with lib/internal/GnuFreeFont/FreeSerif.ttf,
 * which carries 252 codepoints of the Arabic block and 141 of Presentation
 * Forms-B — every glyph this produces. Verified against the font's cmap.
 *
 * WHY A pdf.xml RENDERER AND NOT A PLUGIN. A plugin on AbstractPdf would have
 * been the smaller change, but this store runs production mode against a
 * compiled DI config: a plugin that is not in the compiled interception map
 * never fires, and rebuilding that map means taking the site down for a
 * di:compile. Renderers are resolved from pdf.xml, which is ordinary config —
 * so this lands with a cache flush and no downtime.
 *
 * `draw()` is forked from core because prepareText is private. Keep it in sync
 * on upgrade — diff against
 * vendor/magento/module-sales/Model/Order/Pdf/Items/Invoice/DefaultInvoice.php
 */
class Arabic extends DefaultInvoice
{
    private ?ArabicText $arabicText = null;

    /**
     * EVERY ARGUMENT IS OPTIONAL, deliberately.
     *
     * The compiled DI config has never seen this class, so its factory calls
     * the constructor with nothing at all and each dependency arrives null.
     * Resolving them here is what makes a new class usable in production
     * without a recompile.
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
        ?\Magento\Sales\Model\RtlTextHandler $rtlTextHandler = null,
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
            $data,
            $rtlTextHandler
        );
    }

    /**
     * Forked from core. The only change is prepareText -> hmPrepareText.
     */
    public function draw()
    {
        $order = $this->getOrder();
        $item = $this->getItem();
        $pdf = $this->getPdf();
        $page = $this->getPage();
        $lines = [];

        // draw Product name
        $lines[0][] = [
            'text' => $this->string->split($this->hmPrepareText((string)$item->getName()), 35, true, true),
            'feed' => 35
        ];

        // draw SKU
        $lines[0][] = [
            'text' => $this->string->split($this->hmPrepareText((string)$this->getSku($item)), 17),
            'feed' => 290,
            'align' => 'right',
        ];

        // draw QTY
        $lines[0][] = ['text' => $item->getQty() * 1, 'feed' => 435, 'align' => 'right'];

        // draw item Prices
        $i = 0;
        $prices = $this->getItemPricesForDisplay();
        $feedPrice = 395;
        $feedSubtotal = $feedPrice + 170;
        foreach ($prices as $priceData) {
            if (isset($priceData['label'])) {
                $lines[$i][] = ['text' => $priceData['label'], 'feed' => $feedPrice, 'align' => 'right'];
                $lines[$i][] = ['text' => $priceData['label'], 'feed' => $feedSubtotal, 'align' => 'right'];
                $i++;
            }
            $lines[$i][] = [
                'text' => $priceData['price'],
                'feed' => $feedPrice,
                'font' => 'bold',
                'align' => 'right',
            ];
            $lines[$i][] = [
                'text' => $priceData['subtotal'],
                'feed' => $feedSubtotal,
                'font' => 'bold',
                'align' => 'right',
            ];
            $i++;
        }

        // draw Tax
        $lines[0][] = [
            'text' => $order->formatPriceTxt($item->getTaxAmount()),
            'feed' => 495,
            'font' => 'bold',
            'align' => 'right',
        ];

        // custom options
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

                if ($option['value'] !== null) {
                    if (isset($option['print_value'])) {
                        $printValue = $option['print_value'];
                    } else {
                        $printValue = $this->filterManager->stripTags($option['value']);
                    }
                    $printValue = str_replace(PHP_EOL, ', ', $printValue);
                    $values = explode(', ', $printValue);
                    $text = [];
                    foreach ($values as $value) {
                        foreach ($this->string->split($this->hmPrepareText($value), 50, true, true) as $subValue) {
                            $text[] = $subValue;
                        }
                    }

                    $lines[][] = ['text' => $text, 'feed' => 40];
                }
            }
        }

        $lineBlock = ['lines' => $lines, 'height' => 20, 'shift' => 5];

        $page = $pdf->drawLineBlocks($page, [$lineBlock], ['table_header' => true]);
        $this->setPage($page);
    }

    /**
     * Core's prepareText, with shaping in place of the bare reversal.
     *
     * Latin comes back byte-identical — ArabicText returns the string untouched
     * when it holds no Arabic, so a SKU or an English name is not routed
     * through a transform that has nothing to do.
     */
    private function hmPrepareText(string $string): string
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        return $this->arabicText->render(html_entity_decode($string));
    }
}
