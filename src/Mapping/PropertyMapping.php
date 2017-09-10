<?php
namespace CSVImport\Mapping;

use Zend\View\Renderer\PhpRenderer;

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

    public static function getSidebar(PhpRenderer $view)
    {
        $html = $view->csvPropertySelector($view->translate('Properties:'), false);
        return $html;
    }

    public function processRow(array $row)
    {
        $propertyJson = [];
        $columnMap = isset($this->args['column-property']) ? $this->args['column-property'] : [];
        $urlMap = isset($this->args['column-url']) ? array_keys($this->args['column-url']) : [];
        $referenceMap = isset($this->args['column-reference']) ? array_keys($this->args['column-reference']) : [];
        $multivalueMap = isset($this->args['column-multivalue']) ? array_keys($this->args['column-multivalue']) : [];
        $languageSettings = isset($this->args['column-language']) ? $this->args['column-language'] : [];
        $globalLanguage = isset($this->args['global_language']) ? $this->args['global_language'] : '';
        $multivalueSeparator = $this->args['multivalue_separator'];
        foreach ($row as $index => $values) {
            // consider 'literal' as the default type
            $type = 'literal';
            if (in_array($index, $urlMap)) {
                $type = 'uri';
            }

            if (in_array($index, $referenceMap)) {
                $type = 'resource';
            }

            if (isset($columnMap[$index])) {
                foreach ($columnMap[$index] as $propertyTerm => $propertyId) {
                    if (in_array($index, $multivalueMap)) {
                        $multivalues = explode($multivalueSeparator, $values);
                        $multivalues = array_map('trim', $multivalues);
                        foreach ($multivalues as $value) {
                            switch ($type) {
                                case 'uri':
                                    $propertyJson[$propertyTerm][] = [
                                        '@id' => $value,
                                        'property_id' => $propertyId,
                                        'type' => $type,
                                    ];
                                break;
                                case 'resource':
                                    $propertyJson[$propertyTerm][] = [
                                        'value_resource_id' => $value,
                                        'property_id' => $propertyId,
                                        'type' => $type,
                                    ];
                                break;

                                case 'literal':
                                    $literalPropertyJson = [
                                        '@value' => $value,
                                        'property_id' => $propertyId,
                                        'type' => $type,
                                    ];
                                    if ($globalLanguage !== '') {
                                        $literalPropertyJson['@language'] = $globalLanguage;
                                    }
                                    if (isset($languageSettings[$index])) {
                                        $literalPropertyJson['@language'] = $languageSettings[$index];
                                    }
                                    $propertyJson[$propertyTerm][] = $literalPropertyJson;
                                break;
                            }
                        }
                    } else {
                        switch ($type) {
                            case 'uri':
                                $propertyJson[$propertyTerm][] = [
                                    '@id' => $values,
                                    'property_id' => $propertyId,
                                    'type' => $type,
                                ];

                            break;
                            case 'resource':
                                $propertyJson[$propertyTerm][] = [
                                    'value_resource_id' => $values,
                                    'property_id' => $propertyId,
                                    'type' => $type,
                                ];
                            break;

                            case 'literal':
                                $literalPropertyJson = [
                                    '@value' => $values,
                                    'property_id' => $propertyId,
                                    'type' => $type,
                                ];
                                if ($globalLanguage !== '') {
                                    $literalPropertyJson['@language'] = $globalLanguage;
                                }
                                if (isset($languageSettings[$index])) {
                                    $literalPropertyJson['@language'] = $languageSettings[$index];
                                }
                                $propertyJson[$propertyTerm][] = $literalPropertyJson;

                            break;
                        }
                    }
                }
            }
        }
        return $propertyJson;
    }
}
