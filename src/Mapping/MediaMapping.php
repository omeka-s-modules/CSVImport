<?php
namespace CSVImport\Mapping;

class MediaMapping extends AbstractResourceMapping
{
    protected $label = 'Media data'; // @translate
    protected $resourceType = 'media';

    protected function processGlobalArgs()
    {
        parent::processGlobalArgs();
        parent::processGlobalArgsMedia();
    }

    protected function processCell($index, array $values)
    {
        parent::processCell($index, $values);
        parent::processCellMedia($index, $values);
    }
}
