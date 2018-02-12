<?php declare(strict_types=1);

namespace Shopware\Rest\Controller;

use Shopware\Api\Entity\Entity;
use Shopware\Api\Entity\EntityDefinition;
use Shopware\Api\Entity\Field\AssociationInterface;
use Shopware\Api\Entity\Field\Field;
use Shopware\Api\Entity\Field\ManyToManyAssociationField;
use Shopware\Api\Entity\Field\ManyToOneAssociationField;
use Shopware\Api\Entity\Field\OneToManyAssociationField;
use Shopware\Api\Entity\FieldCollection;
use Shopware\Api\Entity\RepositoryInterface;
use Shopware\Api\Entity\Search\Criteria;
use Shopware\Api\Entity\Search\Parser\QueryStringParser;
use Shopware\Api\Entity\Search\Query\TermQuery;
use Shopware\Api\Entity\Write\GenericWrittenEvent;
use Shopware\Api\Entity\Write\WriteContext;
use Shopware\Rest\ApiContext;
use Shopware\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Shopware\Api\Entity\DefinitionRegistry;
use Shopware\Api\Entity\Write\EntityWriter;

class ApiController extends RestController
{
    public const WRITE_UPDATE = 'update';
    public const WRITE_CREATE = 'create';
    public const WRITE_DELETE = 'delete';

    public function detailAction(Request $request, ApiContext $context): Response
    {
        $path = $this->buildEntityPath($request->getPathInfo());

        $root = $path[0]['entity'];
        $id = $path[count($path) - 1]['value'];

        $definition = $this->get(DefinitionRegistry::class)->get($root);

        $associations = array_column($path, 'entity');
        array_shift($associations);

        if (empty($associations)) {
            $repository = $this->get($definition::getRepositoryClass());
        } else {
            /** @var EntityDefinition $definition */
            $field = $this->getAssociation($definition::getFields(), $associations);

            $referenceClass = $field->getReferenceClass();
            if ($field instanceof ManyToManyAssociationField) {
                $referenceClass = $field->getReferenceDefinition();
            }

            $repository = $this->get($referenceClass::getRepositoryClass());
        }

        /** @var RepositoryInterface $repository */
        $entities = $repository->readDetail([$id], $context->getTranslationContext());

        $entity = $entities->get($id);

        return $this->createResponse(['data' => $entity], $context);
    }

    public function listAction(Request $request, ApiContext $context): Response
    {
        $path = $this->buildEntityPath($request->getPathInfo());

        $first = array_shift($path);

        /** @var EntityDefinition $definition */
        $definition = $first['definition'];

        /** @var RepositoryInterface $repository */
        $repository = $this->get($definition::getRepositoryClass());

        if (empty($path)) {
            $result = $repository->search(
                $this->createListingCriteria($request),
                $context->getTranslationContext()
            );

            return $this->createResponse([
                'data' => $result,
                'total' => $result->getTotal(),
                'aggregations' => $result->getAggregations(),
            ], $context);
        }

        $child = array_pop($path);
        $parent = $first;

        if (!empty($path)) {
            $parent = array_pop($path);
        }

        $criteria = $this->createListingCriteria($request);

        $association = $child['field'];

        $parentDefinition = $parent['definition'];

        $definition = $child['definition'];

        if ($association instanceof ManyToManyAssociationField) {
            /*
             * Example:
             * route:           /api/product/SW1/categories
             * $definition:     \Shopware\Category\Definition\CategoryDefinition
             */
            $definition = $association->getReferenceDefinition();

            //fetch inverse association definition for filter
            $reverse = $definition::getFields()->filter(
                function (Field $field) use ($association) {
                    return $field instanceof ManyToManyAssociationField && $association->getMappingDefinition() === $field->getMappingDefinition();
                }
            );

            //contains now the inverse side association: category.products
            $reverse = $reverse->first();

            /* @var ManyToManyAssociationField $reverse */
            $criteria->addFilter(
                new TermQuery(
                    sprintf('%s.%s.id', $definition::getEntityName(), $reverse->getPropertyName()),
                    $parent['value']
                )
            );
        } elseif ($association instanceof OneToManyAssociationField) {
            /*
             * Example
             * Route:           /api/product/SW1/prices
             * $definition:     \Shopware\Product\Definition\ProductPriceDefinition
             */

            //get foreign key definition of reference
            $foreignKey = $definition::getFields()->getByStorageName(
                $association->getReferenceField()
            );

            $criteria->addFilter(
                new TermQuery(
                    //add filter to parent value: prices.productId = SW1
                    $definition::getEntityName() . '.' . $foreignKey->getPropertyName(),
                    $parent['value']
                )
            );
        } elseif ($association instanceof ManyToOneAssociationField) {
            /*
             * Example
             * Route:           /api/product/SW1/manufacturer
             * $definition:     \Shopware\Product\Definition\ProductManufacturerDefinition
             */

            //get inverse association to filter to parent value
            $reverse = $definition::getFields()->filter(
                function (Field $field) use ($parentDefinition) {
                    return $field instanceof OneToManyAssociationField && $parentDefinition === $field->getReferenceClass();
                }
            );
            $reverse = $reverse->first();

            /* @var OneToManyAssociationField $reverse */
            $criteria->addFilter(
                new TermQuery(
                    //filter inverse association to parent value:  manufacturer.products.id = SW1
                    sprintf('%s.%s.id', $definition::getEntityName(), $reverse->getPropertyName()),
                    $parent['value']
                )
            );
        }

        /** @var RepositoryInterface $repository */
        $repository = $this->get($definition::getRepositoryClass());

        $result = $repository->search($criteria, $context->getTranslationContext());

        return $this->createResponse([
            'data' => $result,
            'total' => $result->getTotal(),
            'aggregations' => $result->getAggregations(),
        ], $context);
    }

    public function createAction(Request $request, ApiContext $context): Response
    {
        return $this->write($request, $context, self::WRITE_CREATE);
    }

    public function updateAction(Request $request, ApiContext $context)
    {
        return $this->write($request, $context, self::WRITE_UPDATE);
    }

    public function deleteAction(Request $request, ApiContext $context): Response
    {
        $path = $this->buildEntityPath($request->getPathInfo());

        $last = $path[count($path) - 1];

        $id = $last['value'];

        $first = array_shift($path);

        /* @var string|EntityDefinition $definition */
        if (count($path) === 0) {
            //first api level call /product/{id}
            $definition = $first['definition'];

            $id = $this->doDelete($context, $definition, $id);

            return $this->createResponse(['data' => $id], $context);
        }

        $child = array_pop($path);
        $parent = $first;
        if (!empty($path)) {
            $parent = array_pop($path);
        }

        $definition = $child['definition'];

        /** @var AssociationInterface $association */
        $association = $child['field'];

        if ($association instanceof OneToManyAssociationField) {
            $id = $this->doDelete($context, $definition, $id);

            return $this->createResponse(['data' => $id], $context);
        }

        // DELETE api/product/{id}/manufacturer/{id}
        if ($association instanceof ManyToOneAssociationField) {
            $id = $this->doDelete($context, $definition, $id);

            return $this->createResponse(['data' => $id], $context);
        }

        // DELETE api/product/{id}/category/{id}
        if ($association instanceof ManyToManyAssociationField) {
            $fields = $definition::getPrimaryKeys();

            $local = $definition::getFields()->getByStorageName(
                $association->getMappingLocalColumn()
            );

            $reference = $definition::getFields()->getByStorageName(
                $association->getMappingReferenceColumn()
            );

            $mapping = [
                $local->getPropertyName() => $parent['value'],
                $reference->getPropertyName() => $id,
            ];

            $writer = $this->get(EntityWriter::class);

            $writer->delete(
                $definition,
                [$mapping],
                WriteContext::createFromTranslationContext($context->getTranslationContext())
            );

            return $this->createResponse(['data' => $mapping], $context);
        }

        throw new \RuntimeException(sprintf('Unsupported association for field %s', $association->getPropertyName()));
    }

    private function write(Request $request, ApiContext $context, string $type): Response
    {
        $payload = $context->getPayload();

        $path = $this->buildEntityPath($request->getPathInfo());

        $last = $path[count($path) - 1];

        if ($type === self::WRITE_UPDATE && isset($last['value'])) {
            $payload['id'] = $last['value'];
        }

        $first = array_shift($path);

        /* @var string|EntityDefinition $definition */
        if (count($path) === 0) {
            $definition = $first['definition'];

            /** @var RepositoryInterface $repository */
            $repository = $this->get($definition::getRepositoryClass());
            $events = $this->executeWriteOperation($definition, $payload, $context, $type);
            $event = $events->getEventByDefinition($definition);

            $entities = $repository->readBasic($event->getIds(), $context->getTranslationContext());

            return $this->createResponse(['data' => $entities->first()], $context);
        }

        $child = array_pop($path);

        $parent = $first;
        if (!empty($path)) {
            $parent = array_pop($path);
        }

        $definition = $child['definition'];

        $association = $child['field'];

        /** @var EntityDefinition $parentDefinition */
        $parentDefinition = $parent['definition'];

        /* @var RepositoryInterface $repository */

        /* @var Entity $entity */
        if ($association instanceof OneToManyAssociationField) {
            $foreignKey = $definition::getFields()
                ->getByStorageName($association->getReferenceField());

            $payload[$foreignKey->getPropertyName()] = $parent['value'];

            $events = $this->executeWriteOperation($definition, $payload, $context, $type);
            $event = $events->getEventByDefinition($definition);

            $repository = $this->get($definition::getRepositoryClass());
            $entities = $repository->readBasic($event->getIds(), $context->getTranslationContext());

            return $this->createResponse(['data' => $entities->first()], $context);
        }

        if ($association instanceof ManyToOneAssociationField) {
            $repository = $this->get($definition::getRepositoryClass());

            $events = $this->executeWriteOperation($definition, $payload, $context, $type);
            $event = $events->getEventByDefinition($definition);

            $entities = $repository->readBasic($event->getIds(), $context->getTranslationContext());
            $entity = $entities->first();

            $foreignKey = $parentDefinition::getFields()
                ->getByStorageName($association->getStorageName());

            $payload = [
                'id' => $parent['value'],
                $foreignKey->getPropertyName() => $entity->getId(),
            ];

            $repository = $this->get($parentDefinition::getRepositoryClass());

            $repository->update([$payload], $context->getTranslationContext());

            return $this->createResponse(['data' => $entity], $context);
        }

        /** @var ManyToManyAssociationField $association */

        /** @var EntityDefinition|string $reference */
        $reference = $association->getReferenceDefinition();

        $events = $this->executeWriteOperation($reference, $payload, $context, $type);
        $event = $events->getEventByDefinition($reference);

        $repository = $this->get($reference::getRepositoryClass());
        $entities = $repository->readBasic($event->getIds(), $context->getTranslationContext());
        $entity = $entities->first();

        $repository = $this->get($parentDefinition::getRepositoryClass());

        $foreignKey = $definition::getFields()->getByStorageName(
            $association->getMappingReferenceColumn()
        );

        $payload = [
            'id' => $parent['value'],
            $association->getPropertyName() => [
                [$foreignKey->getPropertyName() => $entity->getId()],
            ],
        ];

        $repository->update([$payload], $context->getTranslationContext());

        return $this->createResponse(['data' => $entity], $context);
    }

    private function executeWriteOperation(string $definition, array $payload, ApiContext $context, string $type): GenericWrittenEvent
    {
        /** @var EntityDefinition $definition */
        $repository = $this->get($definition::getRepositoryClass());

        /* @var RepositoryInterface $repository */
        switch ($type) {
            case self::WRITE_CREATE:
                return $repository->create([$payload], $context->getTranslationContext());

            case self::WRITE_UPDATE:
            default:
                return $repository->update([$payload], $context->getTranslationContext());
        }
    }

    private function getAssociation(FieldCollection $fields, array $keys): AssociationInterface
    {
        $key = array_shift($keys);

        /** @var AssociationInterface $field */
        $field = $fields->get($key);

        if (empty($keys)) {
            return $field;
        }

        /** @var string|EntityDefinition $reference */
        $reference = $field->getReferenceClass();

        $nested = $reference::getFields();

        return $this->getAssociation($nested, $keys);
    }

    private function buildEntityPath(string $pathInfo)
    {
        $exploded = str_replace('/api/', '', $pathInfo);
        $exploded = explode('/', $exploded);

        $parts = [];
        foreach ($exploded as $index => $part) {
            if ($index % 2) {
                continue;
            }
            if (empty($part)) {
                continue;
            }
            $value = null;
            if (isset($exploded[$index + 1])) {
                $value = $exploded[$index + 1];
            }

            if (empty($parts)) {
                $part = $this->urlToSnakeCase($part);
            } else {
                $part = $this->urlToCamelCase($part);
            }

            $parts[] = [
                'entity' => $part,
                'value' => $value,
            ];
        }

        $parts = array_filter($parts);
        $first = array_shift($parts);

        $registry = $this->get(DefinitionRegistry::class);
        $root = $registry->get($first['entity']);

        $entities = [
            [
                'entity' => $first['entity'],
                'value' => $first['value'],
                'definition' => $root,
                'field' => null,
            ],
        ];

        foreach ($parts as $part) {
            $fields = $root::getFields();

            /** @var AssociationInterface $field */
            $field = $fields->get($part['entity']);
            if (!$field) {
                throw new \InvalidArgumentException(
                    sprintf('')
                );
            }
            $entities[] = [
                'entity' => $part['entity'],
                'value' => $part['value'],
                'definition' => $field->getReferenceClass(),
                'field' => $field,
            ];
        }

        return $entities;
    }

    private function urlToSnakeCase(string $name): string
    {
        return str_replace('-', '_', $name);
    }

    private function urlToCamelCase(string $name): string
    {
        $parts = explode('-', $name);
        $parts = array_map('ucfirst', $parts);

        return lcfirst(implode('', $parts));
    }

    private function createListingCriteria(Request $request): Criteria
    {
        $criteria = new Criteria();
        $criteria->setLimit(10);
        $criteria->setFetchCount(true);

        if ($request->query->has('offset')) {
            $criteria->setOffset((int) $request->query->get('offset'));
        }

        if ($request->query->has('limit')) {
            $criteria->setLimit((int) $request->query->get('limit'));
        }

        if ($request->query->has('query')) {
            $criteria->addFilter(
                QueryStringParser::fromUrl($request->query->get('query'))
            );
        }

        return $criteria;
    }

    /**
     * @param ApiContext              $context
     * @param string|EntityDefinition $definition
     * @param string                  $id
     *
     * @throws \RuntimeException
     *
     * @return array
     */
    private function doDelete(ApiContext $context, $definition, $id): array
    {
        /** @var RepositoryInterface $repository */
        $repository = $this->get($definition::getRepositoryClass());

        $fields = $definition::getPrimaryKeys();

        if ($fields->count() > 1 && empty($context->getPayload())) {
            throw new \RuntimeException(
                sprintf('Entity primary key is defined by multiple columns. Please provide primary key in payload.')
            );
        }

        if ($fields->count() > 1) {
            $mapping = $context->getPayload();
        } else {
            $pk = $fields->first();
            /** @var Field $pk */
            $mapping = [$pk->getPropertyName() => $id];
        }

        $repository->delete([$mapping], $context->getTranslationContext());

        return $mapping;
    }
}
