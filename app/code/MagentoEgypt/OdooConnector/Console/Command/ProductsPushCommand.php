<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Console\Command;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Console\Cli;
use MagentoEgypt\OdooConnector\Model\Api\OdooException;
use MagentoEgypt\OdooConnector\Model\Mapping\MapManager;
use MagentoEgypt\OdooConnector\Model\Product\ProductPushMapper;
use MagentoEgypt\OdooConnector\Model\Product\ProductPusher;
use MagentoEgypt\OdooConnector\Model\Sync\CorrelationId;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Phase 3 (Products, write-back Magento -> Odoo): create/update Odoo
 * product.template from Magento products. Idempotent via the entity map —
 * an existing odoo_id triggers a write (update), never a second create, so
 * re-running never duplicates in Odoo.
 */
class ProductsPushCommand extends Command
{
    private const OPT_LIMIT = 'limit';
    private const OPT_DRY_RUN = 'dry-run';
    private const ENTITY_TYPE = 'product';
    private const ODOO_MODEL = 'product.template';

    private CollectionFactory $productCollectionFactory;
    private ProductPusher $productPusher;
    private ProductPushMapper $pushMapper;
    private MapManager $mapManager;
    private CorrelationId $correlationId;

    public function __construct(
        CollectionFactory $productCollectionFactory,
        ProductPusher $productPusher,
        ProductPushMapper $pushMapper,
        MapManager $mapManager,
        CorrelationId $correlationId,
        ?string $name = null
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productPusher = $productPusher;
        $this->pushMapper = $pushMapper;
        $this->mapManager = $mapManager;
        $this->correlationId = $correlationId;
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName('odoo:products:push')
            ->setDescription('Push Magento products to Odoo (create/update product.template; idempotent via the entity map).')
            ->addOption(self::OPT_LIMIT, 'l', InputOption::VALUE_REQUIRED, 'Max Magento products to push', '50')
            ->addOption(self::OPT_DRY_RUN, null, InputOption::VALUE_NONE, 'Preview only; perform no Odoo writes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = max(1, (int)$input->getOption(self::OPT_LIMIT));
        $dryRun = (bool)$input->getOption(self::OPT_DRY_RUN);
        $correlation = $this->correlationId->generate();

        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'price'])->setPageSize($limit)->setCurPage(1);

        $output->writeln(sprintf(
            '<info>Pushing up to %d Magento products to Odoo%s (correlation %s)</info>',
            $limit,
            $dryRun ? ' [DRY RUN]' : '',
            $correlation
        ));

        $created = 0;
        $updated = 0;
        $failed = 0;

        foreach ($collection as $product) {
            $sku = (string)$product->getSku();
            $values = $this->pushMapper->toOdooValues($product);
            $map = $this->mapManager->findByNaturalKey(self::ENTITY_TYPE, $sku, 0);
            $existingOdooId = ($map !== null && $map->getData('odoo_id')) ? (int)$map->getData('odoo_id') : null;

            if ($dryRun) {
                $output->writeln(sprintf(
                    '  %s sku=%s name=%s odoo_id=%s',
                    $existingOdooId !== null ? 'UPDATE' : 'CREATE',
                    $sku,
                    $values['name'],
                    $existingOdooId ?? '(new)'
                ));
                $existingOdooId !== null ? $updated++ : $created++;
                continue;
            }

            try {
                $result = $this->productPusher->push($product, $correlation);
                $result['action'] === 'create' ? $created++ : $updated++;
            } catch (OdooException $e) {
                $output->writeln(sprintf('  <error>FAILED sku=%s: %s</error>', $sku, $e->getMessage()));
                $failed++;
            }
        }

        $output->writeln(sprintf('<info>Done.</info> created=%d updated=%d failed=%d', $created, $updated, $failed));

        return $failed > 0 ? Cli::RETURN_FAILURE : Cli::RETURN_SUCCESS;
    }
}
