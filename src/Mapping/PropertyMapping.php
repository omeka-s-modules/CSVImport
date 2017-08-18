<?php
namespace CSVImport\Mapping;

use CSVImport\Mapping\AbstractMapping;

class PropertyMapping extends AbstractMapping
{

    public static function getLabel()
    {
        return "Properties"; // @translate
    }

    public static function getName()
    {
        return 'property-selector';
    }

    public static function getSidebar($view)
    {
        $html = $view->csvPropertySelector($view->translate('Properties: '), false);
        return $html;
    }

    public function processRow($row, $itemJson = [])
    {
        $propertyJson = [];
        $columnMap = isset($this->args['column-property']) ? $this->args['column-property'] : [];
        $urlMap = isset($this->args['column-url']) ? array_keys($this->args['column-url']) : [];
        $referenceMap = isset($this->args['column-reference']) ? array_keys($this->args['column-reference']) : [];
        $multivalueMap = isset($this->args['column-multivalue']) ? array_keys($this->args['column-multivalue']) : [];
        $languageSettings = isset($this->args['column-language']) ? $this->args['column-language'] : [];
        $globalLanguage = isset($this->args['global-language']) ? $this->args['global-language'] : '';
        $multivalueSeparator = $this->args['multivalue-separator'];
        foreach($row as $index => $values) {
            // consider 'literal' as the default type
            $type = 'literal';
            if (in_array($index, $urlMap)) {
                $type = 'uri';
            }

            if (in_array($index, $referenceMap)) {
                $type = 'resource';
            }

            if(isset($columnMap[$index])) {
                foreach($columnMap[$index] as $propertyId) {
                    if(in_array($index, $multivalueMap)) {
                        $multivalues = explode($multivalueSeparator, $values);
                        foreach($multivalues as $value) {
                            switch ($type) {
                                case 'uri':
                                    $propertyJson[$propertyId][] = [
                                        '@id'         => $value,
                                        'property_id' => $propertyId,
                                        'type'        => $type,
                                    ];
                                break;
                                case 'resource':
                                    $propertyJson[$propertyId][] = [
                                        'value_resource_id' => $value,
                                        'property_id'       => $propertyId,
                                        'type'              => $type,
                                    ];
                                break;

                                case 'literal':
                                    $literalPropertyJson =  [
                                        '@value'      => $value,
                                        'property_id' => $propertyId,
                                        'type'        => $type,
                                    ];
                                    if ($globalLanguage !== '') {
                                        $literalPropertyJson['@language'] = $globalLanguage;
                                    }
                                    if (isset($languageSettings[$index])) {
                                        $literalPropertyJson['@language'] = $languageSettings[$index];
                                    }
                                    $propertyJson[$propertyId][] = $literalPropertyJson;
                                break;
                            }
                        }
                    } else {
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
                                    'value_resource_id' => $values,
                                    'property_id'       => $propertyId,
                                    'type'              => $type,
                                ];
                            break;

                            case 'literal':
                                $literalPropertyJson =  [
                                    '@value'      => $values,
                                    'property_id' => $propertyId,
                                    'type'        => $type,
                                ];
                                $this->logger->debug($globalLanguage);
                                if ($globalLanguage !== '') {
                                    $literalPropertyJson['@language'] = $globalLanguage;
                                }
                                if (isset($languageSettings[$index])) {
                                    $literalPropertyJson['@language'] = $languageSettings[$index];
                                }
                                $propertyJson[$propertyId][] = $literalPropertyJson;
                                
                            break;
                        }
                    }
                }
            }
        }
        return $propertyJson;
    }
}
