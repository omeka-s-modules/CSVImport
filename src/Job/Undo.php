<?php
namespace CSVImport\Job;

use Omeka\Job\AbstractJob;

class Undo extends AbstractJob
{
    public function perform()
    {
        $jobId = $this->getArg('jobId');
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $response = $api->search('csvimport_records', array('job_id' => $jobId, 'remote_type' => 'item'));
        $csvItems = $response->getContent();
        if ($csvItems) {
            foreach ($csvItems as $csvItem) {
                $csvResponse = $api->delete('csvimport_records', $csvItem->id());
                if ($csvResponse->isError()) {
                }

                $itemResponse = $api->delete('items', $csvItem->item()->id());
                if ($itemResponse->isError()) {
                }
            }
        }
    }
}
