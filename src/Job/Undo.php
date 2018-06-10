<?php
namespace CSVImport\Job;

use Omeka\Job\AbstractJob;
use Omeka\Stdlib\Message;

class Undo extends AbstractJob
{
    public function perform()
    {
        $services = $this->getServiceLocator();
        $logger = $services->get('Omeka\Logger');
        $api = $services->get('Omeka\ApiManager');

        $jobId = $this->getArg('jobId');
        $response = $api->search('csvimport_entities', ['job_id' => $jobId]);
        $csvEntities = $response->getContent();
        if ($csvEntities) {
            foreach ($csvEntities as $key => $csvEntity) {
                if ($this->shouldStop()) {
                    $logger->warn(new Message(
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
