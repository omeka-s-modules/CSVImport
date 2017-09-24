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

        $this->findResourceFromIdentifier = $this->getServiceLocator()->get('ControllerPluginManager')
            ->get('findResourceFromIdentifier');

        $resourceType = $this->args['resource_type'];
        $isMedia = $resourceType === 'media';

        $config = $this->getServiceLocator()->get('Config');
        $mediaAdapters = $config['csv_import']['media_ingester_adapter'];
        $mediaMap = isset($this->args['column-media_source']) ? $this->args['column-media_source'] : [];
        $action = $this->args['action'];

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

                    // Check if the media is already fetched.
                    $mediaDatumJson = $this->checkExistingMedia($mediaDatumJson);

                    $data[] = $mediaDatumJson;
                }
            }
        }

        return $this->args['resource_type'] === 'media'
            ? ($data ? reset($data) : [])
            : ['o:media' => $data];
    }

    protected function checkExistingMedia(array $mediaDatumJson)
    {
        $identifier = $mediaDatumJson['o:source'];
        $resourceType = 'media';
        $identifierProperty = 'media_source=' . $mediaDatumJson['o:ingester'];

        $findResourceFromIdentifier = $this->findResourceFromIdentifier;
        $resourceId = $findResourceFromIdentifier($identifier, $identifierProperty, $resourceType);

        if ($resourceId) {
            $mediaDatumJson['o:id'] = $resourceId;
        }
        return $mediaDatumJson;
    }
}
