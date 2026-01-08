<?php
namespace CSVImport\Api\Adapter;

use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;
use CSVImport\Entity\CSVImportMappingModel;
use Doctrine\ORM\QueryBuilder;

class MappingModelAdapter extends AbstractEntityAdapter
{
    public function getResourceName()
    {
        return 'csvimport_mapping_models';
    }

    public function getRepresentationClass()
    {
        return \CSVImport\Api\Representation\MappingModelRepresentation::class;
    }

    public function getEntityClass()
    {
        return CSVImportMappingModel::class;
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
            $errorStore->addError('o-module-csvimport-mappingmodel:name', 'A model must have a name to save it.'); // @translate
        }

        if (!$entity->getMapping()) {
            $errorStore->addError('o-module-csvimport-mappingmodel:mapping', 'Mapping model must exists.'); // @translate
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
