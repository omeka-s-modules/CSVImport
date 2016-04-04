<?php
namespace CSVImport\Mapping;


class MediaMapping
{
    
    protected $args;
    
    protected $api;
    
    protected $logger;
    
    protected $serviceLocator;

    public function __construct($args, $serviceLocator)
    {
        $this->args = $args;
        $this->logger = $serviceLocator->get('Omeka\Logger');
        $this->api = $serviceLocator->get('Omeka\ApiManager');
        $this->serviceLocator = $serviceLocator;
    }
    
    public static function getLabel($view)
    {
        return "Media Import";
    }
    
    public static function getName()
    {
        return 'media';
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
                    $mediaDatumJson = array(
                        'o:ingester'     => $ingester,
                        'o:source'   => trim($mediaDatum),
                    );
                    switch($ingester) {
                        case 'html':
                            $mediaDatumJson['html'] = $mediaDatum;
                        break;
                        
                        case 'url':
                            $mediaDatumJson['ingest_url'] = trim($mediaDatum);
                        break;
                    }
                    $mediaJson['o:media'][] = $mediaDatumJson;
                }
            }
        }

        return $mediaJson;
    }
}