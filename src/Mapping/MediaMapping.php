<?php
namespace CSVImport\Mapping;



class MediaMapping extends AbstractMapping
{
    public static function getLabel()
    {
        return "Media import"; // @translate
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
        foreach ($row as $index => $values) {
            //split $values into an array, so people can have more than one file
            //in the column
            $mediaData = explode($multivalueSeparator, $values);
            $mediaData = array_map('trim', $mediaData);

            if (array_key_exists($index, $mediaMap)) {
                $ingester = $mediaMap[$index];
                foreach ($mediaData as $mediaDatum) {
                    if (empty($mediaDatum)) {
                        continue;
                    }
                    $mediaDatumJson = [
                        'o:ingester' => $ingester,
                        'o:source' => $mediaDatum,
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
