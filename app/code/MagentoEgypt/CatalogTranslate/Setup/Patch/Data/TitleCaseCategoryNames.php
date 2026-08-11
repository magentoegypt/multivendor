<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\CatalogTranslate\Setup\Patch\Data;

use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

/**
 * One casing convention for the English category labels.
 *
 * WHY TITLE CASE, AND NOT A COIN TOSS
 * -----------------------------------
 * The catalog has two authoring regimes and the casing splits cleanly along
 * them. Categories authored Arabic-first were given deliberate English
 * translations at store-view scope, and ALL FIFTEEN of those are Title Case:
 * "Kids & Toys", "Building Materials", "Cement Materials", "Wall Panels",
 * "Reinforcement Steel", "Table Lamps & Floor Lamps", "Wall Lamps",
 * "Ceiling Lights", "Dairy Products", "Detergents & Laundry Supplies",
 * "Beauty Tools", "Personal Care", "Frozen Products", "Online Games",
 * "Dolls & Toys". Categories authored English-first were never revisited and
 * are lowercase or sentence case.
 *
 * Counting multi-word labels alone gives 22 Title Case against 17 sentence
 * case, which is close enough to be a coin toss. What breaks the tie is that
 * every one of the fifteen labels somebody actually sat down and wrote is Title
 * Case, and the sentence-case group is entirely labels nobody ever edited. The
 * deliberate set is the house style; the rest is drift.
 *
 * Title Case here: capitalise every word except internal short function words
 * (and, or, the, a, an, of, for, with, to, in, on), always capitalising the
 * first and last. Exactly one label in this batch contains such a word — 87
 * "Inks and Printing Supplies" — so that clause fires once.
 *
 * THREE THINGS THAT ARE CASING EVEN THOUGH THEY LOOK LIKE MORE
 * ------------------------------------------------------------
 *   81  hp    → HP    A two-letter initialism. Naive Title Case gives "Hp",
 *                     which is wrong AS CASING. The Arabic اتش بي spells it
 *                     letter by letter, confirming it is read as letters.
 *   82  DELL  → Dell  The only row here that DOWNCASES. All-caps is not Dell's
 *                     own spelling, and reducing it changes no letters.
 *   99  tv    → TV    Same as HP: "Tv" would be a casing error, not a style
 *                     preference. NOT expanded to "Televisions", even though
 *                     the Arabic تليفزيونات is plural — that would be wording.
 *
 * And one that is NOT a brand exception: 83 acer → Acer. The logo is set in
 * lowercase, but a lowercase logotype is a typographic treatment; Acer writes
 * itself "Acer" in prose. Plain Title Case applies.
 *
 * ID 64: "mobile&tablet" → "Mobile & Tablet", spaces included. You cannot apply
 * "capitalise every word" before word boundaries exist, so tokenising it is a
 * prerequisite of the casing decision rather than an edit riding along with it.
 * "&" is punctuation, not a lexeme — no word is added, removed, respelled or
 * reordered — and all four ampersand labels in the house-style set space it.
 *
 * That reasoning deliberately does NOT extend to 131 "hand bag" → "handbag" or
 * 133 "back bag" → "backpack". Closing those spaces substitutes a different
 * lexical item, which is an English spelling call the merchant owns. Opening a
 * space around punctuation substitutes nothing. Both ship uncased-only as
 * "Hand Bag" and "Back Bag" even though closing them would have made 131 agree
 * with 137 "Men's Handbag" and 133 with 139 "Men's Backpack" — that consistency
 * win is precisely the temptation a casing pass has to refuse.
 *
 * THIS REVISES AN EARLIER DECISION OF MINE
 * ----------------------------------------
 * 141/142 ("Women's clothing" / "Men's clothing") and 143/151 ("Top clothes")
 * were set to sentence case a few commits ago, and are now Title Case. That
 * earlier choice existed to keep 141 and 142 a matching pair and 143 and 151
 * byte-identical. Title Case preserves both of those exactly, so the recase
 * costs nothing the earlier edit was protecting — while leaving them alone
 * would strand four sentence-case labels among forty-nine.
 *
 * SCOPE: CASING ONLY. Several of these labels are also poor English. Every one
 * ships with its CURRENT WORDING, cased. See the class docblock list below; the
 * rewording is a merchant decision on its own ticket.
 *
 * All writes are at store 0. Verified before writing: every one of these 49
 * categories has its own Arabic store-1 value, so changing the default cannot
 * reach the Arabic storefront, and none has a store-3 override, so store 0 is
 * what English shoppers actually see.
 *
 * URLs are untouched — url_key is a separate attribute Magento only derives
 * from the name at creation time.
 *
 * LEFT ALONE ON PURPOSE, all merchant calls:
 *   89  "External Hards"  "hards" is not a noun — external hard drives
 *   88  "Electronic Flash" these are USB flash drives, not camera strobes
 *   131 "Hand Bag"        normally the closed compound "handbag"
 *   133 "Back Bag"        the product is a backpack
 *   93  "Sport Shoes"     normally "sports shoes"
 *   130 "Women's Bag"     singular beside its plural sibling "Men's Bags"
 *   56  "Flat"            a shoe style normally pluralised "flats"
 *   62  "Classic"         the Arabic رسمي means formal/dress shoes
 *   134 "Soiree Bag"      English retail says "evening bag"
 *   135 "Suitcase"        the Arabic حقيبة سفر is luggage generally, and it
 *                         sits under "Women's Bag", which is odd parenting
 *   143/151 "Top Clothes" awkward for tops; must change together if at all
 *   87  keeps "and" while 64 uses "&" — harmonising means rewriting words
 */
class TitleCaseCategoryNames implements DataPatchInterface
{
    /**
     * id => [expected current value, Title Case value]. All at store 0.
     *
     * The expected value is the portability guard: a row is rewritten only
     * while it still holds exactly the string this map was built against, so
     * an id reused on another install is skipped and logged rather than
     * silently recased into something wrong.
     */
    private const CASING = [
        // Footwear
        53  => ['shoes',                      'Shoes'],
        54  => ['men',                        'Men'],
        55  => ['women',                      'Women'],
        56  => ['flat',                       'Flat'],
        57  => ['High heels',                 'High Heels'],
        62  => ['classic',                    'Classic'],
        93  => ['sport shoes',                'Sport Shoes'],

        // Phones and tablets
        64  => ['mobile&tablet',              'Mobile & Tablet'],
        65  => ['mobile',                     'Mobile'],
        66  => ['tablet',                     'Tablet'],
        69  => ['xiaomi',                     'Xiaomi'],
        70  => ['apple',                      'Apple'],

        // Furniture
        74  => ['furniture',                  'Furniture'],
        75  => ['home furniture',             'Home Furniture'],
        76  => ['office furniture',           'Office Furniture'],

        // Computing
        77  => ['computer',                   'Computer'],
        78  => ['laptop',                     'Laptop'],
        79  => ['printers',                   'Printers'],
        80  => ['storage media',              'Storage Media'],
        81  => ['hp',                         'HP'],
        82  => ['DELL',                       'Dell'],
        83  => ['acer',                       'Acer'],
        85  => ['laser printers',             'Laser Printers'],
        86  => ['Color printers',             'Color Printers'],
        87  => ['Inks and printing supplies', 'Inks and Printing Supplies'],
        88  => ['Electronic flash',           'Electronic Flash'],
        89  => ['External hards',             'External Hards'],
        90  => ['Memory cards',               'Memory Cards'],

        // Sports
        91  => ['sports',                     'Sports'],
        92  => ['sports equipment',           'Sports Equipment'],

        // Appliances
        98  => ['cookers',                    'Cookers'],
        99  => ['tv',                         'TV'],

        // Bags
        129 => ['bags',                       'Bags'],
        130 => ["women's bag",                "Women's Bag"],
        131 => ['hand bag',                   'Hand Bag'],
        132 => ['Shoulder bag',               'Shoulder Bag'],
        133 => ['back bag',                   'Back Bag'],
        134 => ['Soiree bag',                 'Soiree Bag'],
        135 => ['suitcase',                   'Suitcase'],
        136 => ["Men's bags",                 "Men's Bags"],
        137 => ["Men's handbag",              "Men's Handbag"],
        138 => ["Men's crossbody bag",        "Men's Crossbody Bag"],
        139 => ["Men's backpack",             "Men's Backpack"],

        // Clothing
        140 => ['clothes',                    'Clothes'],
        141 => ["Women's clothing",           "Women's Clothing"],
        142 => ["Men's clothing",             "Men's Clothing"],
        143 => ['Top clothes',                'Top Clothes'],
        151 => ['Top clothes',                'Top Clothes'],

        // Other
        210 => ['Kitchen tools',              'Kitchen Tools'],
    ];

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly EavConfig $eavConfig,
        private readonly LoggerInterface $logger
    ) {
    }

    public static function getDependencies(): array
    {
        //  After the artifact strip: that patch produces the exact "from"
        //  values this one asserts on for 142, 143 and 151.
        return [StripCategoryNameArtifacts::class];
    }

    public function getAliases(): array
    {
        return [];
    }

    public function apply(): self
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->startSetup();

        try {
            $nameId = (int) $this->eavConfig
                ->getAttribute(\Magento\Catalog\Model\Category::ENTITY, 'name')
                ->getAttributeId();

            $table   = $this->moduleDataSetup->getTable('catalog_category_entity_varchar');
            $written = 0;
            $skipped = 0;

            foreach (self::CASING as $categoryId => [$expected, $cased]) {
                $current = $connection->fetchOne(
                    $connection->select()
                        ->from($table, 'value')
                        ->where('entity_id = ?', $categoryId)
                        ->where('attribute_id = ?', $nameId)
                        ->where('store_id = ?', 0)
                );

                if ($current !== false && (string) $current === $cased) {
                    //  Already correct — a re-run, or a human got there first.
                    $skipped++;
                    continue;
                }

                if ($current === false || (string) $current !== $expected) {
                    $this->logger->info(sprintf(
                        'CatalogTranslate: category %d not recased — expected "%s", found "%s".',
                        $categoryId,
                        $expected,
                        $current === false ? '(no row)' : (string) $current
                    ));
                    $skipped++;
                    continue;
                }

                $connection->update(
                    $table,
                    ['value' => $cased],
                    [
                        'entity_id = ?'    => $categoryId,
                        'attribute_id = ?' => $nameId,
                        'store_id = ?'     => 0,
                    ]
                );
                $written++;
            }

            $this->logger->info(
                sprintf('CatalogTranslate: %d category names recased, %d left alone.', $written, $skipped)
            );
        } catch (\Throwable $e) {
            $this->logger->error('CatalogTranslate: ' . $e->getMessage());
        }

        $connection->endSetup();

        return $this;
    }
}
