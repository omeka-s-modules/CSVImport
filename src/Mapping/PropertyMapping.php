<?php
namespace CSVImport\Mapping;

use Zend\View\Renderer\PhpRenderer;

class PropertyMapping extends AbstractMapping
{
    protected $label = 'Properties'; // @translate
    protected $name = 'property-selector';

    public function getSidebar(PhpRenderer $view)
    {
        return $view->csvPropertySelector($view->translate('Properties'), false);
    }

    public function processRow(array $row)
    {
        // Reset the data and the map between rows.
        $this->setHasErr(false);
        $data = [];

        // First, pull in the global settings.

        // Set columns.
        if (isset($this->args['column-property'])) {
            $propertyMap = $this->args['column-property'];
            foreach ($propertyMap as $column => $property) {
                $data[key($property)] = [];
            }
        }

        // Return if no column.
        if (empty($propertyMap)) {
            return $data;
        }

        // Get mappings for options.
        if (isset($this->args['column-url'])) {
            $urlMap = $this->args['column-url'];
        }
        if (isset($this->args['column-reference'])) {
            $referenceMap = $this->args['column-reference'];
        }
        if (isset($this->args['column-language'])) {
            $languageMap = $this->args['column-language'];
        }

        // Get default option values.
        $globalLanguage = isset($this->args['global_language']) ? $this->args['global_language'] : '';

        $multivalueMap = isset($this->args['column-multivalue']) ? $this->args['column-multivalue'] : [];
        $multivalueSeparator = $this->args['multivalue_separator'];
        foreach ($row as $index => $values) {
            if (isset($propertyMap[$index])) {
                // Consider 'literal' as the default type.
                $type = 'literal';
                if (isset($urlMap[$index])) {
                    $type = 'uri';
                } elseif (isset($referenceMap[$index])) {
                    $type = 'resource';
                }

                foreach ($propertyMap[$index] as $propertyTerm => $propertyId) {
                    if (empty($multivalueMap[$index])) {
                        $values = [$values];
                    } else {

                        $values = explode($multivalueSeparator, $values);
                        $values = array_map(function ($v) { return trim($v); }, $values);
                    }
                    $values = array_filter($values, 'strlen');
                    foreach ($values as $value) {
                        switch ($type) {
                            case 'uri':
                                $data[$propertyTerm][] = [
                                    '@id' => $value,
                                    'property_id' => $propertyId,
                                    'type' => $type,
                                ];
                                break;

                            case 'resource':
                                $data[$propertyTerm][] = [
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
                                $data[$propertyTerm][] = $literalPropertyJson;
                                break;
                        }
                    }
                }
            }
        }

        return $data;
    }
}
