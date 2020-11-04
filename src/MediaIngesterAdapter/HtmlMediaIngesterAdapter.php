<?php
namespace CSVImport\MediaIngesterAdapter;

class HtmlMediaIngesterAdapter implements MediaIngesterAdapterInterface
{
    public function getJson($mediaDatum)
    {
        $mediaDatumJson = [];
        $mediaDatumJson['html'] = $mediaDatum;
        $mediaDatumJson['o:source'] = null;
        return $mediaDatumJson;
    }
}
