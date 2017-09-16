<?php
namespace CSVImport\Mapping;

use Zend\View\Renderer\PhpRenderer;

class MediaSourceMapping extends AbstractMapping
{
    public static function getLabel()
    {
        return 'Media source'; // @translate
    }

    public static function getName()
    {
        return 'media-source';
    }

    public static function getSidebar(PhpRenderer $view)
    {
        return $view->mediaSourceSidebar();
    }

    public function processRow(array $row)
    {
        $data = [];

        $config = $this->getServiceLocator()->get('Config');
        $mediaAdapters = $config['csv_import']['media_ingester_adapter'];
        $mediaMap = isset($this->args['media-source']) ? $this->args['media-source'] : [];

        $isMedia = $this->args['resource_type'] === 'media';

        $multivalueMap = isset($this->args['column-multivalue']) ? $this->args['column-multivalue'] : [];
        $multivalueSeparator = $this->args['multivalue_separator'];
        foreach ($row as $index => $values) {
            if (array_key_exists($index, $mediaMap)) {
                if (empty($multivalueMap[$index])) {
                    $values = [$values];
                } else {
                    $values = explode($multivalueSeparator, $values);
                    $values = array_map('trim', $values);
                    if ($isMedia) {
                        array_splice($values, 1);
                    }
                }

                $ingester = $mediaMap[$index];
                $values = array_filter($values, 'strlen');
                foreach ($values as $mediaDatum) {
                    $mediaDatumJson = [
                        'o:ingester' => $ingester,
                        'o:source' => $mediaDatum,
                    ];
                    if (isset($mediaAdapters[$ingester])) {
                        $adapter = new $mediaAdapters[$ingester];
                        $mediaDatumJson = array_merge($mediaDatumJson, $adapter->getJson($mediaDatum));
                    }
                    $data[] = $mediaDatumJson;
                }
            }
        }

        return $this->args['resource_type'] === 'media'
            ? ($data ? reset($data) : [])
            : ['o:media' => $data];
    }
}
