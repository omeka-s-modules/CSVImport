<?php
namespace CSVImport\Mapping;

use CSVImport\Mapping\AbstractMapping;
use CSVImport\MediaIngesterAdapter\HtmlMediaIngesterAdapter;
use CSVImport\MediaIngesterAdapter\UrlMediaIngesterAdapter;

class MediaMapping extends AbstractMapping
{
    public static function getLabel()
    {
        return "Media Import";
    }

    public static function getName()
    {
        return 'media-import';
    }

    public static function getSidebar($view)
    {
        return $view->mediaSidebar();

    }

    public function processRow($row)
    {
        $config = $this->getServiceLocator()->get('Config');
        $mediaAdapters = $config['csv_import_media_ingester_adapter'];
        $mediaJson = ['o:media' => []];
        $mediaMap = isset($this->args['media']) ? $this->args['media'] : [];
        $multivalueMap = isset($this->args['column-multivalue']) ? array_keys($this->args['column-multivalue']) : [];
        $multivalueSeparator = $this->args['multivalue-separator'];
        foreach($row as $index => $values) {
            //split $values into an array, so people can have more than one file
            //in the column
            $mediaData = explode($multivalueSeparator, $values);

            if (array_key_exists($index, $mediaMap)) {
                $ingester = $mediaMap[$index];
                $this->logger->debug($mediaData);
                foreach($mediaData as $mediaDatum) {
                    $mediaDatum = trim($mediaDatum);
                    if(empty($mediaDatum)) {
                        continue;
                    }
                    $mediaDatumJson = [
                        'o:ingester' => $ingester,
                        'o:source'   => $mediaDatum,
                    ];
                    if (isset($mediaAdapters[$ingester])) {
                        $adapter = new $mediaAdapters[$ingester];
                        $mediaDatumJson = array_merge($mediaDatumJson, $adapter->getJson($mediaDatum));
                    }
                    $mediaJson['o:media'][] = $mediaDatumJson;
                }
            }
        }

        return $mediaJson;
    }
}