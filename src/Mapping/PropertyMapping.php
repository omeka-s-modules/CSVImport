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
        $propertyJson = [];
        $columnMap = isset($this->args['column-property']) ? $this->args['column-property'] : [];
        $uriMap = isset($this->args['column-uri']) ? array_keys($this->args['column-uri']) : [];
        $multivalueMap = isset($this->args['column-multivalue']) ? array_keys($this->args['column-multivalue']) : [];
        $multivalueSeparator = $this->args['multivalue-separator'];
        foreach($row as $index => $values) {
            $type = in_array($index, $uriMap) ? 'uri' : 'literal';
            if(isset($columnMap[$index])) {
                foreach($columnMap[$index] as $propertyId) {
                    if(in_array($index, $multivalueMap)) {
                        $multivalues = explode($multivalueSeparator, $values);
                        foreach($multivalues as $value) {
                            if ($type == 'uri') {
                                $propertyJson[$propertyId][] = [
                                        '@id'         => $value,
                                        'property_id' => $propertyId,
                                        'type'        => $type,
                                ];
                            } else {
                                $propertyJson[$propertyId][] = [
                                        '@value'      => $value,
                                        'property_id' => $propertyId,
                                        'type'        => $type,
                                ];
                            }
                        }
                    } else {
                        if ($type == 'uri') {
                            $propertyJson[$propertyId][] = [
                                    '@id'         => $values,
                                    'property_id' => $propertyId,
                                    'type'        => $type,
                            ];
                        } else {
                            $propertyJson[$propertyId][] = [
                                    '@value'      => $values,
                                    'property_id' => $propertyId,
                                    'type'        => $type,
                            ];
                        }
                    }
                }
            }
        }
        return $propertyJson;
    }
}
