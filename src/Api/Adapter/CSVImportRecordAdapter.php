<?php
namespace CSVImport\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class CSVimportRecordAdapter extends AbstractEntityAdapter
{
    public function getEntityClass()
    {
        return 'CSVimport\Entity\OmekaimportRecord';
    }
    
    public function getResourceName()
    {
        return 'csvimport_records';
    }
    
    public function getRepresentationClass()
    {
        return 'CSVImport\Api\Representation\CSVImportRecordRepresentation';
    }
    
    public function buildQuery(QueryBuilder $qb, array $query)
    {
        if (isset($query['endpoint'])) {
            $qb->andWhere($qb->expr()->eq(
                $this->getEntityClass() . '.endpoint',
                $this->createNamedParameter($qb, $query['endpoint']))
            );
        }

        if (isset($query['job_id'])) {
            $qb->andWhere($qb->expr()->eq(
                $this->getEntityClass() . '.job',
                $this->createNamedParameter($qb, $query['job_id']))
            );
        }
        if (isset($query['item_id'])) {
            $qb->andWhere($qb->expr()->eq(
                $this->getEntityClass() . '.item',
                $this->createNamedParameter($qb, $query['item_id']))
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
        if (isset($data['o:item']['o:id'])) {
            $item = $this->getAdapter('items')->findEntity($data['o:item']['o:id']);
            $entity->setItem($item);
        }
        if (isset($data['o:item_set']['o:id'])) {
            $itemSet = $this->getAdapter('item_sets')->findEntity($data['o:item_set']['o:id']);
            $entity->setItemSet($itemSet);
        }

        if (isset($data['last_modified'])) {
            $entity->setLastModified($data['last_modified']);
        }
    }
}
