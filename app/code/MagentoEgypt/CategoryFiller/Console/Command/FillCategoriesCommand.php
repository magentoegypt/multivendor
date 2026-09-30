<?php
declare(strict_types=1);

namespace MagentoEgypt\CategoryFiller\Console\Command;

use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * bin/magento magentoegypt:category:fill [--min=12] [--dry-run]
 *
 * For each Hub Market top-level category (resolved by url_key) counts the
 * enabled products already in it or its sub-categories and creates just enough
 * new simple products to reach --min. Safe to re-run: a category that is
 * already full is skipped, and SKUs are deterministic (HM-<key>-NN) so an
 * interrupted run resumes instead of duplicating.
 *
 * The new products have no images: attach real ones afterwards.
 */
class FillCategoriesCommand extends Command
{
    /** url_key => [base price EGP, 12 product names]. */
    private const CATALOG = [
        'super-market' => [40, ['Basmati Rice 5kg', 'Sunflower Oil 1.8L', 'White Sugar 2kg', 'Table Salt 700g', 'Spaghetti Pasta 500g', 'Tomato Paste 400g', 'Black Tea 100 Bags', 'Instant Coffee 200g', 'Long Life Milk 1L', 'Chickpeas Can 400g', 'Fava Beans Can 400g', 'Cornflakes 500g']],
        'pharmacy' => [60, ['Paracetamol 500mg 20 Tablets', 'Vitamin C 1000mg 30 Tablets', 'Adhesive Bandages 40 Pack', 'Digital Thermometer', 'Hand Sanitizer 250ml', 'Antiseptic Solution 100ml', 'Multivitamin 60 Capsules', 'Blood Pressure Monitor', 'Face Masks 50 Pack', 'Cough Syrup 120ml', 'Oral Rehydration Salts 10 Sachets', 'First Aid Kit']],
        'furniture' => [2500, ['Wooden Dining Table', 'Upholstered Dining Chair', 'Three Seater Sofa', 'Coffee Table', 'TV Stand', 'Bookshelf 5 Tier', 'Double Bed Frame', 'Bedside Table', 'Wardrobe Two Door', 'Office Desk', 'Shoe Rack', 'Accent Armchair']],
        'clothes' => [350, ['Cotton Crew Neck T-Shirt', 'Slim Fit Jeans', 'Hooded Sweatshirt', 'Formal Shirt', 'Chino Trousers', 'Summer Dress', 'Denim Jacket', 'Polo Shirt', 'Jogger Pants', 'Knit Cardigan', 'Abaya Classic Black', 'Sports Shorts']],
        'fmcg' => [45, ['Laundry Detergent 3kg', 'Dishwashing Liquid 750ml', 'Toilet Paper 12 Rolls', 'Paper Towels 6 Rolls', 'Shampoo 400ml', 'Toothpaste 100ml', 'Bath Soap 6 Pack', 'Fabric Softener 2L', 'Facial Tissues 6 Boxes', 'Multi Surface Cleaner 1L', 'Garbage Bags 30 Pack', 'Deodorant Spray 150ml']],
        'games' => [180, ['Building Blocks 200 Pieces', 'Remote Control Car', 'Plush Teddy Bear', 'Jigsaw Puzzle 500 Pieces', 'Kids Art Set', 'Board Game Family Edition', 'Toy Kitchen Set', 'Doll House', 'Wooden Train Set', 'Play Dough 8 Colors', 'Educational Alphabet Cards', 'Kids Football']],
        'health' => [220, ['Vitamin E Face Cream 50ml', 'Matte Lipstick', 'Foundation 30ml', 'Mascara Volume', 'Eau de Parfum 100ml', 'Body Lotion 400ml', 'Argan Hair Oil 100ml', 'Face Wash Gel 150ml', 'Nail Polish Set', 'Eyeshadow Palette', 'Sunscreen SPF50 60ml', 'Micellar Water 400ml']],
        'electronics' => [900, ['Wireless Earbuds', 'Bluetooth Speaker', 'Power Bank 20000mAh', 'USB-C Fast Charger 25W', 'Smart Watch', 'Laptop Backpack', 'Wireless Mouse', 'Mechanical Keyboard', 'HD Webcam', 'Portable SSD 512GB', '4K HDMI Cable 2m', 'Phone Case Clear']],
        'home-appliances' => [1800, ['Electric Kettle 1.7L', 'Air Fryer 4L', 'Microwave Oven 25L', 'Blender 1.5L', 'Steam Iron', 'Vacuum Cleaner 2000W', 'Toaster Two Slice', 'Rice Cooker 1.8L', 'Hand Mixer', 'Table Fan 16 Inch', 'Coffee Maker', 'Food Processor']],
        'fresh-food' => [60, ['Fresh Tomatoes 1kg', 'Cucumbers 1kg', 'Bananas 1kg', 'Red Apples 1kg', 'Fresh Oranges 2kg', 'Chicken Breast 1kg', 'Minced Beef 500g', 'Fresh Eggs 30 Pack', 'Full Fat Cheese 500g', 'White Bread Loaf', 'Fresh Strawberries 500g', 'Potatoes 2kg']],
    ];

    private State $appState;
    private CategoryCollectionFactory $categoryCollectionFactory;
    private ProductCollectionFactory $productCollectionFactory;
    private ProductFactory $productFactory;
    private ProductRepositoryInterface $productRepository;
    private CategoryLinkManagementInterface $categoryLinkManagement;
    private StoreManagerInterface $storeManager;

    public function __construct(
        State $appState,
        CategoryCollectionFactory $categoryCollectionFactory,
        ProductCollectionFactory $productCollectionFactory,
        ProductFactory $productFactory,
        ProductRepositoryInterface $productRepository,
        CategoryLinkManagementInterface $categoryLinkManagement,
        StoreManagerInterface $storeManager
    ) {
        $this->appState = $appState;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->categoryLinkManagement = $categoryLinkManagement;
        $this->storeManager = $storeManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('magentoegypt:category:fill')
            ->setDescription('Create simple products so each top-level category has at least --min products')
            ->addOption('min', null, InputOption::VALUE_REQUIRED, 'Minimum products per category', '12')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Only report what would be created');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->appState->setAreaCode('adminhtml');
        } catch (LocalizedException $e) {
            // area already set
        }
        $min = max(1, (int) $input->getOption('min'));
        $dry = (bool) $input->getOption('dry-run');
        $websiteIds = array_keys($this->storeManager->getWebsites());

        foreach (self::CATALOG as $urlKey => [$basePrice, $names]) {
            $category = $this->categoryCollectionFactory->create()
                ->addAttributeToFilter('url_key', $urlKey)
                ->addAttributeToFilter('level', 2)
                ->setPageSize(1)
                ->getFirstItem();
            if (!$category->getId()) {
                $output->writeln("<comment>$urlKey: category not found, skipped</comment>");
                continue;
            }
            $ids = array_filter(explode(',', $category->getAllChildren()));
            $have = $this->productCollectionFactory->create()
                ->addAttributeToFilter('status', Status::STATUS_ENABLED)
                ->addCategoriesFilter(['in' => $ids])
                ->getSize();
            $missing = $min - $have;
            $output->writeln(sprintf('%s (id %d): %d products, %d to create', $urlKey, $category->getId(), $have, max(0, $missing)));
            if ($missing <= 0 || $dry) {
                continue;
            }
            $created = 0;
            foreach ($names as $i => $name) {
                if ($created >= $missing) {
                    break;
                }
                $sku = sprintf('HM-%s-%02d', strtoupper($urlKey), $i + 1);
                if ($this->skuExists($sku)) {
                    $this->categoryLinkManagement->assignProductToCategories($sku, [(int) $category->getId()]);
                    continue;
                }
                $product = $this->productFactory->create();
                $product->setTypeId(Type::TYPE_SIMPLE)
                    ->setAttributeSetId($product->getDefaultAttributeSetId())
                    ->setSku($sku)
                    ->setName($name)
                    ->setUrlKey(strtolower($sku))
                    ->setPrice($basePrice + ($i % 6) * ceil($basePrice * 0.15))
                    ->setWeight(1)
                    ->setStatus(Status::STATUS_ENABLED)
                    ->setVisibility(Visibility::VISIBILITY_BOTH)
                    ->setWebsiteIds($websiteIds)
                    ->setStockData(['use_config_manage_stock' => 1, 'qty' => 100, 'is_in_stock' => 1])
                    ->setCategoryIds([(int) $category->getId()]);
                $this->productRepository->save($product);
                $created++;
            }
            $output->writeln("  created $created");
        }
        $output->writeln('Done. Reindex (bin/magento indexer:reindex) and flush the cache.');
        return Command::SUCCESS;
    }

    private function skuExists(string $sku): bool
    {
        try {
            $this->productRepository->get($sku);
            return true;
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return false;
        }
    }
}
