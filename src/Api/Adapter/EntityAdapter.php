<?php
namespace CSVImport\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class EntityAdapter extends AbstractEntityAdapter
{
    public function getEntityClass()
    {
        return 'CSVImport\Entity\CSVImportEntity';
    }
    
    public function getResourceName()
    {
        return 'csvimport_entities';
    }
    
    public function getRepresentationClass()
    {
        return 'CSVImport\Api\Representation\EntityRepresentation';
    }
    
    public function buildQuery(QueryBuilder $qb, array $query)
    {
        if (isset($query['job_id'])) {
            $qb->andWhere($qb->expr()->eq(
                $this->getEntityClass() . '.job',
                $this->createNamedParameter($qb, $query['job_id']))
            );
        }
        if (isset($query['entity_id'])) {
            $qb->andWhere($qb->expr()->eq(
                $this->getEntityClass() . '.entity',
                $this->createNamedParameter($qb, $query['entity_id']))
            );
        }
        if (isset($query['resource_type'])) {
            $qb->andWhere($qb->expr()->eq(
                $this->getEntityClass() . '.resource_type',
                $this->createNamedParameter($qb, $query['resource_type']))
            );
        }
    }

    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        $data = $request->getContent();
        if (isset($data['o:job']['o:id'])) {
            $job = $this->getAdapter('jobs')->findEntity($data['o:job']['o:id']);
            $entity->setJob($job);
        }
        
        //@todo redo this for generalized entities somehow
        if (isset($data['o:entity']['o:id'])) {
            $resourceType = $data['o:entity']['resource_type'];
            $dataKey = 'o:' . rtrim($resourceType, 's');
            $otherEntity = $this->getAdapter($resourceType)->findEntity($data[$dataKey]['o:id']);
            $entity->setEntity($otherEntity);
        }
    }
}
