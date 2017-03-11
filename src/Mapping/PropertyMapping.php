<?php
namespace CSVImport\Mapping;

use CSVImport\Mapping\AbstractMapping;

class PropertyMapping extends AbstractMapping
{

    public static function getLabel()
    {
        return "Properties";
    }

    public static function getName()
    {
        return 'property-selector';
    }

    public static function getSidebar($view)
    {
        $html = $view->csvPropertySelector('Properties: ', false);
        return $html;
    }

    public function processRow($row, $itemJson = [])
    {
        $this->logger->debug('processRow');
        $propertyJson = [];
        $columnMap = isset($this->args['column-property']) ? $this->args['column-property'] : [];
        $urlMap = isset($this->args['column-url']) ? array_keys($this->args['column-url']) : [];
        $referenceMap = isset($this->args['column-reference']) ? array_keys($this->args['column-reference']) : [];
        $multivalueMap = isset($this->args['column-multivalue']) ? array_keys($this->args['column-multivalue']) : [];
        $multivalueSeparator = $this->args['multivalue-separator'];
        foreach($row as $index => $values) {
            $this->logger->debug('row index: ' . $index);
            $type = 'literal';
            if (in_array($index, $urlMap)) {
                $type = 'uri';
            }

            if (in_array($index, $referenceMap)) {
                $type = 'resource';
            }

            if(isset($columnMap[$index])) {
                $this->logger->debug('has column map');
                foreach($columnMap[$index] as $propertyId) {
                    $this->logger->debug('propId: ' . $propertyId);
                    if(in_array($index, $multivalueMap)) {
                        $this->logger->debug('multivalue');
                        $multivalues = explode($multivalueSeparator, $values);
                        foreach($multivalues as $value) {
                            switch ($type) {
                                case 'uri':
                                case 'resource':
                                    $propertyJson[$propertyId][] = [
                                        '@id'         => $value,
                                        'property_id' => $propertyId,
                                        'type'        => $type,
                                    ];
                                break;

                                case 'literal':
                                    $propertyJson[$propertyId][] = [
                                        '@value'      => $value,
                                        'property_id' => $propertyId,
                                        'type'        => $type,
                                    ];
                                break;
                            }
                        }
                    } else {
                        $this->logger->debug('single value');
                        $this->logger->debug($type);
                        switch ($type) {
                            case 'uri':
                                $propertyJson[$propertyId][] = [
                                    '@id'         => $values,
                                    'property_id' => $propertyId,
                                    'type'        => $type,
                                ];
                                
                            break;
                            case 'resource':
                                $propertyJson[$propertyId][] = [
                                '@id'               => $values,
                                'value_resource_id' => $values,
                                'property_id'       => $propertyId,
                                'type'              => $type,
                                ];
                            break;

                            case 'literal':
                                $propertyJson[$propertyId][] = [
                                '@value'      => $values,
                                'property_id' => $propertyId,
                                'type'        => $type,
                                ];
                            break;
                        }
                    }
                }
            }
        }
        $this->logger->debug($propertyJson);
        return $propertyJson;
    }
}
