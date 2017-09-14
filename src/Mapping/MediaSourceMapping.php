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
        $config = $this->getServiceLocator()->get('Config');
        $mediaAdapters = $config['csv_import']['media_ingester_adapter'];
        $mediaJson = ['o:media' => []];
        $mediaMap = isset($this->args['column-media_source']) ? $this->args['column-media_source'] : [];
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
                    $mediaJson['o:media'][] = $mediaDatumJson;
                }
            }
        }
        return $mediaJson;
    }
}
