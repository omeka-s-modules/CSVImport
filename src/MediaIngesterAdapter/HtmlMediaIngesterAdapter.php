<?php
namespace CSVImport\MediaIngesterAdapter;

class HtmlMediaIngesterAdapter implements MediaIngesterAdapterInterface
{
    public function getJson($mediaDatum)
    {
        $mediaDatumJson = [];
        $mediaDatumJson['html'] = $mediaDatum;
        $mediaDatumJson['dcterms:title'] = [
            ['@value' => '',
                'property_id' => 1,
                'type' => 'literal',
            ],
        ];
        return $mediaDatumJson;
    }
}
