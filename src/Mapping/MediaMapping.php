<?php
namespace CSVImport\Mapping;

use CSVImport\Mapping\AbstractMapping;

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
        $mediaJson = array('o:media' => array());
        $mediaMap = isset($this->args['media']) ? $this->args['media'] : [];
        $multivalueMap = isset($this->args['column-multivalue']) ? array_keys($this->args['column-multivalue']) : [];
        $multivalueSeparator = $this->args['multivalue-separator'];
        foreach($row as $index => $values) {
            //split $values into an array, so people can have more than one file
            //in the column
            $mediaData = explode($multivalueSeparator, $values);

            if (array_key_exists($index, $mediaMap)) {
                $ingester = $mediaMap[$index];
                foreach($mediaData as $mediaDatum) {
                    $mediaDatum = trim($mediaDatum);
                    if(empty($mediaDatum)) {
                        continue;
                    }
                    $mediaDatumJson = array(
                        'o:ingester'     => $ingester,
                        'o:source'   => $mediaDatum,
                    );
                    switch($ingester) {
                        case 'html':
                            $mediaDatumJson['html'] = $mediaDatum;
                            $mediaDatumJson['dcterms:title'] = [
                                    ['@value'      => '',
                                     'property_id' => 1,
                                     'type'        => 'literal'
                                    ]
                                ];
                        break;

                        case 'url':
                            $mediaDatumJson['ingest_url'] = $mediaDatum;
                        break;
                    }
                    $mediaJson['o:media'][] = $mediaDatumJson;
                }
            }
        }

        return $mediaJson;
    }
}