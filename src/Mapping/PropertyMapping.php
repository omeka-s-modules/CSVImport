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
        $languageSettings = isset($this->args['column-language']) ? $this->args['column-language'] : [];
        $globalLanguage = isset($this->args['global_language']) ? $this->args['global_language'] : '';

        $multivalueMap = isset($this->args['column-multivalue']) ? $this->args['column-multivalue'] : [];
        $multivalueSeparator = $this->args['multivalue_separator'];
        foreach ($row as $index => $values) {
            if (isset($columnMap[$index])) {
                // consider 'literal' as the default type
                $type = 'literal';
                if (in_array($index, $urlMap)) {
                    $type = 'uri';
                }
                if (in_array($index, $referenceMap)) {
                    $type = 'resource';
                }

                foreach ($columnMap[$index] as $propertyTerm => $propertyId) {
                    if (empty($multivalueMap[$index])) {
                        $values = [$values];
                    } else {
                        $values = explode($multivalueSeparator, $values);
                        $values = array_map('trim', $values);
                    }

                    $values = array_filter($values, 'strlen');
                    foreach ($values as $value) {
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
                }
            }
        }

        return $propertyJson;
    }
}
