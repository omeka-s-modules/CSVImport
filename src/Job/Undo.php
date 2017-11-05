<?php
namespace CSVImport\Job;

use Omeka\Job\AbstractJob;
use Omeka\Stdlib\Message;

class Undo extends AbstractJob
{
    public function perform()
    {
        $jobId = $this->getArg('jobId');
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $response = $api->search('csvimport_entities', ['job_id' => $jobId]);
        $csvEntities = $response->getContent();
        if ($csvEntities) {
            foreach ($csvEntities as $key => $csvEntity) {
                if ($this->shouldStop()) {
                    $this->logger = $services->get('Omeka\Logger');
                    $this->logger->warn(new Message(
                        'The job "Undo" was stopped: %d/%d resources processed.', // @translate
                        $key, count($csvEntities)
                    ));
                    break;
                }
                try {
                    $csvResponse = $api->delete('csvimport_entities', $csvEntity->id());
                    $entityResponse = $api->delete($csvEntity->resourceType(), $csvEntity->entityId());
                } catch (\Exception $e) {
                    // Nothing to do: already deleted.
                    // TODO Implement on delete cascade in the entity CSVImportEntity.
                }
            }
        }
    }
}
