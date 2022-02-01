<?php
namespace CSVImport\Mapping;

use Laminas\View\Renderer\PhpRenderer;

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
            foreach ($propertyMap as $property) {
                $data[key($property)] = [];
            }
        }

        // Return if no column.
        if (empty($propertyMap)) {
            return $data;
        }

        // Get mappings for options.
        if (isset($this->args['column-data-type'])) {
            $dataTypeMap = $this->args['column-data-type'];
        }
        if (isset($this->args['column-language'])) {
            $languageMap = $this->args['column-language'];
        }
        if (isset($this->args['column-private-values'])) {
            $privateValuesMap = $this->args['column-private-values'];
        }

        $dataTypeAdapters = $this->getDataTypeAdapters();

        // Get default option values.
        $globalLanguage = isset($this->args['global_language']) ? $this->args['global_language'] : '';

        $multivalueMap = isset($this->args['column-multivalue']) ? $this->args['column-multivalue'] : [];
        $multivalueSeparator = $this->args['multivalue_separator'];
        foreach ($row as $index => $values) {
            if (empty($multivalueMap[$index])) {
                $values = [trim($values)];
            } else {
                $values = explode($multivalueSeparator, $values);
                $values = array_map(function ($v) {
                    return trim($v);
                }, $values);
            }
            $values = array_filter($values, 'strlen');

            if (isset($propertyMap[$index])) {
                // Consider 'literal' as the default type.
                $type = 'literal';
                if (isset($dataTypeMap[$index])) {
                    $type = $dataTypeMap[$index];
                }
                $typeAdapter = 'literal';
                if (isset($dataTypeAdapters[$type])) {
                    $typeAdapter = $dataTypeAdapters[$type];
                }

                $privateValues = !empty($privateValuesMap[$index]);

                foreach ($propertyMap[$index] as $propertyTerm => $propertyId) {
                    foreach ($values as $value) {
                        $valueData = [];
                        switch ($typeAdapter) {
                            case 'uri':
                                // Check if a label is provided after the url.
                                // Note: A url has no space, but a uri may have.
                                if (strpos($value, ' ')) {
                                    list($valueId, $valueLabel) = explode(' ', $value, 2);
                                    $valueLabel = trim($valueLabel);
                                } else {
                                    $valueId = $value;
                                    $valueLabel = null;
                                }
                                $valueData = [
                                    '@id' => $valueId,
                                    'property_id' => $propertyId,
                                    'type' => $type,
                                    'o:label' => $valueLabel,
                                ];
                                break;

                            case 'resource':
                                $valueData = [
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
                                if (isset($languageMap[$index])) {
                                    $literalPropertyJson['@language'] = $languageMap[$index];
                                }
                                $valueData = $literalPropertyJson;
                                break;
                        }

                        if (!$valueData) {
                            continue;
                        }

                        if ($privateValues) {
                            $valueData['is_public'] = false;
                        }
                        $data[$propertyTerm][] = $valueData;
                    }
                }
            }
        }

        return $data;
    }

    protected function getDataTypeAdapters()
    {
        $dataTypeAdapters = [];

        $config = $this->getServiceLocator()->get('CSVImport\Config');
        $dataTypeConfig = $config['data_types'];
        foreach ($dataTypeConfig as $id => $configEntry) {
            $dataTypeAdapters[$id] = $configEntry['adapter'];
        }
        return $dataTypeAdapters;
    }
}
