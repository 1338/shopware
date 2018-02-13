<?php declare(strict_types=1);

namespace Shopware\DbalIndexing\Product;

use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;
use Shopware\Api\Entity\Dbal\EntityDefinitionQueryHelper;
use Shopware\Api\Entity\Search\Criteria;
use Shopware\Api\Entity\Search\Query\TermQuery;
use Shopware\Api\Entity\Write\GenericWrittenEvent;
use Shopware\Api\Product\Definition\ProductCategoryDefinition;
use Shopware\Api\Product\Definition\ProductDefinition;
use Shopware\Api\Product\Definition\ProductMediaDefinition;
use Shopware\Api\Product\Event\Product\ProductWrittenEvent;
use Shopware\Api\Product\Event\ProductMedia\ProductMediaWrittenEvent;
use Shopware\Api\Product\Repository\ProductRepository;
use Shopware\Api\Shop\Repository\ShopRepository;
use Shopware\Api\Shop\Struct\ShopBasicStruct;
use Shopware\Category\Extension\CategoryPathBuilder;
use Shopware\Context\Struct\TranslationContext;
use Shopware\DbalIndexing\Common\RepositoryIterator;
use Shopware\DbalIndexing\Event\ProgressAdvancedEvent;
use Shopware\DbalIndexing\Event\ProgressFinishedEvent;
use Shopware\DbalIndexing\Event\ProgressStartedEvent;
use Shopware\DbalIndexing\Indexer\IndexerInterface;
use Shopware\Defaults;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProductIndexer implements IndexerInterface
{
    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var CategoryPathBuilder
     */
    private $pathBuilder;

    /**
     * @var ShopRepository
     */
    private $shopRepository;

    public function __construct(
        ProductRepository $productRepository,
        Connection $connection,
        EventDispatcherInterface $eventDispatcher,
        CategoryPathBuilder $pathBuilder,
        ShopRepository $shopRepository
    ) {
        $this->productRepository = $productRepository;
        $this->connection = $connection;
        $this->eventDispatcher = $eventDispatcher;
        $this->pathBuilder = $pathBuilder;
        $this->shopRepository = $shopRepository;
    }

    public function index(\DateTime $timestamp): void
    {
        $shop = $this->getDefaultShop();

        $context = TranslationContext::createFromShop($shop);

        $this->pathBuilder->update(Defaults::ROOT_CATEGORY, $context);

        $iterator = new RepositoryIterator($this->productRepository, $context);

        $this->eventDispatcher->dispatch(
            ProgressStartedEvent::NAME,
            new ProgressStartedEvent('Start building product category tree and parent inheritance', $iterator->getTotal())
        );

        while ($ids = $iterator->fetchIds()) {
            $this->refreshJoinIds($ids, $context);

            $this->indexCategoryAssignment($ids, $context);

            $this->eventDispatcher->dispatch(
                ProgressAdvancedEvent::NAME,
                new ProgressAdvancedEvent(count($ids))
            );
        }

        $this->eventDispatcher->dispatch(
            ProgressFinishedEvent::NAME,
            new ProgressFinishedEvent('Finished building product category tree and parent inheritance')
        );
    }

    public function refresh(GenericWrittenEvent $event): void
    {
        $this->refreshJoinIdsByEvents($event);

        $this->connection->transactional(function() use ($event) {
            $ids = $this->getRefreshedProductIds($event);

            $this->indexCategoryAssignment($ids, $event->getContext());
        });
    }

    private function indexCategoryAssignment(array $ids, TranslationContext $context): void
    {
        if (empty($ids)) {
            return;
        }

        $categories = $this->fetchCategories($ids, $context);

        foreach ($categories as $productId => $mapping) {
            $categoryIds = array_filter(explode('||', (string) $mapping['ids']));
            $categoryIds = array_map(
                function (string $bytes) {
                    return Uuid::fromString($bytes)->toString();
                },
                $categoryIds
            );

            $categoryIds = array_merge(
                explode('|', (string) $mapping['paths']),
                $categoryIds
            );

            $categoryIds = array_keys(array_flip(array_filter($categoryIds)));

            $this->connection->executeUpdate(
                'UPDATE product SET category_tree = :tree WHERE id = :id AND version_id = :version',
                [
                    'id' => $productId,
                    'tree' => json_encode($categoryIds),
                    'version' => Uuid::fromString($context->getVersionId())->getBytes()
                ]
            );
        }
    }

    private function fetchCategories(array $ids, TranslationContext $context): array
    {
        $query = $this->connection->createQueryBuilder();
        $query->select([
            'product.id as product_id',
            "GROUP_CONCAT(category.path SEPARATOR '|') as paths",
            "GROUP_CONCAT(HEX(category.id) SEPARATOR '||') as ids",
        ]);
        $query->from('product');
        $query->leftJoin('product', 'product_category', 'mapping', 'mapping.product_id = product.category_join_id AND product.version_id = mapping.product_version_id');
        $query->leftJoin('mapping', 'category', 'category', 'category.id = mapping.category_id AND category.version_id = :live');
        $query->addGroupBy('product.id');

        $query->andWhere('product.id IN (:ids)');
        $query->andWhere('product.version_id = :version');

        $query->setParameter('version', Uuid::fromString($context->getVersionId())->getBytes());
        $query->setParameter('live', Uuid::fromString(Defaults::LIVE_VERSION)->getBytes());

        $bytes = EntityDefinitionQueryHelper::uuidStringsToBytes($ids);

        $query->setParameter('ids', $bytes, Connection::PARAM_STR_ARRAY);

        return $query->execute()->fetchAll(\PDO::FETCH_GROUP | \PDO::FETCH_UNIQUE);
    }

    private function getRefreshedProductIds(GenericWrittenEvent $event): array
    {
        $productEvent = $event->getEventByDefinition(ProductCategoryDefinition::class);

        if (!$productEvent) {
            return [];
        }

        $ids = [];

        foreach ($productEvent->getIds() as $id) {
            $ids[] = $id['productId'];
        }

        return $ids;
    }

    private function getDefaultShop(): ShopBasicStruct
    {
        $criteria = new Criteria();
        $criteria->addFilter(new TermQuery('shop.isDefault', true));
        $result = $this->shopRepository->search($criteria, TranslationContext::createDefaultContext());

        return $result->first();
    }

    private function refreshJoinIds(array $ids = [], TranslationContext $context)
    {
        $version = Uuid::fromString($context->getVersionId())->getBytes();
        if (empty($ids)) {
            $this->connection->executeUpdate(
                'UPDATE product SET 
                product.category_join_id = IFNULL((SELECT product_category.product_id FROM product_category WHERE product_category.product_id = product.id AND product.version_id = product_category.product_version_id LIMIT 1), product.parent_id),
                product.media_join_id    = IFNULL((SELECT product_media.product_id FROM product_media WHERE product_media.product_id = product.id AND product.version_id = product_media.version_id LIMIT 1), product.parent_id)
                WHERE product.version_id = :version',
                ['version' => $version]
            );

            $this->connection->executeUpdate(
                'UPDATE product as variant, product as parent
                 SET
                    variant.tax_join_id = IFNULL(variant.tax_id, parent.tax_id),
                    variant.manufacturer_join_id = IFNULL(variant.product_manufacturer_id, parent.product_manufacturer_id),
                    variant.unit_join_id = IFNULL(variant.unit_id, parent.unit_id)
                 WHERE variant.parent_id = parent.id
                 AND variant.version_id = parent.version_id
                 AND variant.version_id = :version',
                ['version' => $version]
            );

            $this->connection->executeUpdate(
                'UPDATE product as variant
                 SET
                    variant.tax_join_id = variant.tax_id,
                    variant.manufacturer_join_id = variant.product_manufacturer_id,
                    variant.unit_join_id = variant.unit_id
                 WHERE variant.parent_id IS NULL 
                 AND variant.version_id = :version',
                ['version' => $version]
            );

            return;
        }

        $bytes = array_map(function($id) {
            return Uuid::fromString($id)->getBytes();
        }, $ids);

        $this->connection->executeUpdate(
            'UPDATE product SET 
                product.category_join_id = IFNULL((SELECT product_category.product_id FROM product_category WHERE product_category.product_id = product.id AND product.version_id = product_category.product_version_id LIMIT 1), product.parent_id),
                product.media_join_id    = IFNULL((SELECT product_media.product_id FROM product_media WHERE product_media.product_id = product.id AND product.version_id = product_media.version_id LIMIT 1), product.parent_id)
                WHERE product.version_id = :version
                AND product.id IN (:ids)',
            ['ids' => $bytes, 'version' => $version],
            ['ids' => Connection::PARAM_STR_ARRAY, 'version' => \PDO::PARAM_STR]
        );

        $this->connection->executeUpdate(
            'UPDATE product as variant, product as parent
             SET
                variant.tax_join_id = IFNULL(variant.tax_id, parent.tax_id),
                variant.manufacturer_join_id = IFNULL(variant.product_manufacturer_id, parent.product_manufacturer_id),
                variant.unit_join_id = IFNULL(variant.unit_id, parent.unit_id)
             WHERE (variant.parent_id = parent.id)
             AND variant.id IN (:ids)
             AND variant.version_id = parent.version_id
             AND variant.version_id = :version',
            ['ids' => $bytes, 'version' => $version],
            ['ids' => Connection::PARAM_STR_ARRAY, 'version' => \PDO::PARAM_STR]
        );

        $this->connection->executeUpdate(
            'UPDATE product as variant
             SET
                variant.tax_join_id = variant.tax_id,
                variant.manufacturer_join_id = variant.product_manufacturer_id,
                variant.unit_join_id = variant.unit_id
             WHERE variant.parent_id IS NULL
             AND variant.version_id = :version 
             AND variant.id IN (:ids)',
            ['ids' => $bytes, 'version' => $version],
            ['ids' => Connection::PARAM_STR_ARRAY, 'version' => \PDO::PARAM_STR]
        );
    }

    private function refreshJoinIdsByEvents(GenericWrittenEvent $event)
    {
        /** @var ProductWrittenEvent|null $productWritten */
        $productWritten = $event->getEventByDefinition(ProductDefinition::class);

        $categoryWritten = $event->getEventByDefinition(ProductCategoryDefinition::class);

        $ids = $productWritten ? $productWritten->getIds() : [];

        if ($categoryWritten) {
            $ids = array_merge($ids, array_column($categoryWritten->getIds(), 'productId'));
        }

        $ids = array_filter(array_unique($ids));
        if (!empty($ids)) {
            $this->refreshJoinIds($ids, $event->getContext());
        }

        /** @var ProductMediaWrittenEvent|null $mediaWritten */
        $mediaWritten = $event->getEventByDefinition(ProductMediaDefinition::class);
        
        if ($mediaWritten) {
            $this->mediaWritten($mediaWritten->getIds(), $mediaWritten->getContext());
        }
    }

    private function mediaWritten(array $mediaIds, TranslationContext $context)
    {
        $version = Uuid::fromString($context->getVersionId())->getBytes();

        $bytes = array_map(function($id) {
            return Uuid::fromString($id)->getBytes();
        }, $mediaIds);

        $this->connection->executeUpdate('
            UPDATE product, product_media
              SET product.media_join_id = product_media.product_id

            WHERE product_media.id IN (:ids)
            AND product.version_id = product_media.version_id
            AND product.version_id = :version
            AND product_media.product_id = product.id',
            ['ids' => $bytes, 'version' => $version],
            ['ids' => Connection::PARAM_STR_ARRAY, 'version' => \PDO::PARAM_STR]
        );
    }
}
