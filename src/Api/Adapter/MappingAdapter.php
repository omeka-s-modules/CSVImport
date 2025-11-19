<?php
namespace CSVImport\Api\Adapter;

use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;
use CSVImport\Entity\CSVImportMapping;
use Doctrine\ORM\QueryBuilder;

class MappingAdapter extends AbstractEntityAdapter
{
    public function getResourceName()
    {
        return 'csvimport_mappings';
    }

    public function getRepresentationClass()
    {
        return \CSVImport\Api\Representation\MappingRepresentation::class;
    }

    public function getEntityClass()
    {
        return CSVImportMapping::class;
    }

    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        $data = $request->getContent();

        if (isset($data['name'])) {
            $entity->setName($data['name']);
        }

        if (isset($data['mapping'])) {
            $entity->setMapping($data['mapping']);
        }
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        if (!$entity->getName()) {
            $errorStore->addError('o-module-csvimport-mapping:name', 'A model must have a name to save it.'); // @translate
        }

        if (!$entity->getMapping()) {
            $errorStore->addError('o-module-csvimport-mapping:mapping', 'Mapping must exists.'); // @translate
        }
    }

    public function buildQuery(QueryBuilder $qb, array $query)
    {
        if (isset($query['name'])) {
            $qb->andWhere($qb->expr()->eq(
                'omeka_root.name',
                $this->createNamedParameter($qb, $query['name']))
            );
        }
    }
}
