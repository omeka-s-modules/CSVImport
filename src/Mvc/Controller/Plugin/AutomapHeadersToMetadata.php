<?php
namespace CSVImport\Mvc\Controller\Plugin;

use Laminas\Mvc\Controller\Plugin\AbstractPlugin;

class AutomapHeadersToMetadata extends AbstractPlugin
{
    /**
     * @var array
     */
    protected $configCsvImport;

    /**
     * @var array
     */
    protected $options;

    /**
     * Automap a list of headers to a list of metadata.
     *
     * @param array $headers
     * @param string $resourceType
     * @param array $options Associative array of options:
     * - check_names_alone (boolean)
     * @return array Associative array of the index of the headers as key and
     * the matching metadata as value. Only mapped headers are set.
     */
    public function __invoke(array $headers, $resourceType = null, array $options = null)
    {
        $automaps = [];

        $this->options = $options;

        $headers = $this->cleanSpaces($headers);

        // Prepare the standard lists to check against.
        $lists = [];
        $automapLists = [];

        // Prepare the list of names and labels one time to speed up process.
        $propertyLists = $this->listTerms();

        // Because some terms and labels are not standardized (foaf:givenName is
        // not foaf:givenname), the process must be done case sensitive first.
        $lists['names'] = array_combine(
            array_keys($propertyLists['names']),
            array_keys($propertyLists['names']));
        $lists['lower_names'] = array_map('strtolower', $lists['names']);
        $lists['labels'] = array_combine(
            array_keys($propertyLists['names']),
            array_keys($propertyLists['labels']));
        $lists['lower_labels'] = array_map('strtolower', $lists['labels']);

        $checkNamesAlone = !empty($options['check_names_alone']);
        if ($checkNamesAlone) {
            $lists['local_names'] = array_map(function ($v) {
                $w = explode(':', $v);
                return end($w);
            }, $lists['names']);
            $lists['lower_local_names'] = array_map('strtolower', $lists['local_names']);
            $lists['local_labels'] = array_map(function ($v) {
                $w = explode(':', $v);
                return end($w);
            }, $lists['labels']);
            $lists['lower_local_labels'] = array_map('strtolower', $lists['local_labels']);
        }

        foreach ($headers as $index => $header) {
            $lowerHeader = strtolower($header);
            foreach ($automapLists as $listName => $list) {
                $toSearch = strpos($listName, 'lower_') === 0 ? $lowerHeader : $header;
                $found = array_search($toSearch, $list, true);
                if ($found) {
                    $automaps[$index] = $automapList[$found];
                    continue;
                }
            }

            switch ($resourceType) {
                case 'item_sets':
                case 'items':
                case 'media':
                case 'resources':
                    // Check strict term name, like "dcterms:title", sensitively
                    // then insensitively, then term label like "Dublin Core : Title"
                    // sensitively then insensitively too. Because all the lists
                    // contains the same keys in the same order, the process can
                    // be done in one step.
                    foreach ($lists as $listName => $list) {
                        $toSearch = strpos($listName, 'lower_') === 0 ? $lowerHeader : $header;
                        $found = array_search($toSearch, $list, true);
                        if ($found) {
                            $property = $propertyLists['names'][$found];
                            $automaps[$index] = $property;
                            continue 3;
                        }
                    }
                    break;
                case 'users':
                    break;
            }
        }

        return $this->normalizeAutomapsForForm($automaps, $resourceType);
    }

    /**
     * Prepare automaps to be used in a form, and filter it with resource type.
     *
     * @param array $automaps
     * @param string $resourceType
     * @return array
     */
    protected function normalizeAutomapsForForm(array $automaps, $resourceType)
    {
        $result = [];
        $controller = $this->getController();
        foreach ($automaps as $index => $automap) {
            if (is_object($automap)) {
                if ($automap->getJsonLdType() === 'o:Property') {
                    $value = [];
                    $value['name'] = 'property';
                    $value['value'] = $automap->id();
                    $value['label'] = $controller->translate($automap->label());
                    $value['class'] = 'property';
                    $value['special'] = ' data-property-id="' . $automap->id() . '"';
                    $value['multiple'] = true;
                    $result[$index] = $value;
                }
            }
        }
        return $result;
    }

    /**
     * Return the list of properties by names and labels.
     *
     * @return array Associative array of term names and term labels as key
     * (ex: "dcterms:title" and "Dublin Core : Title") in two subarrays ("names"
     * "labels", and properties as value.
     * Note: Some terms are badly standardized (in foaf, the label "Given name"
     * matches "foaf:givenName" and "foaf:givenname"), so, in that case, the
     * index is added to the label, except the first property.
     */
    protected function listTerms()
    {
        $result = [];
        $vocabularies = $this->getController()->api()->search('vocabularies')->getContent();
        foreach ($vocabularies as $vocabulary) {
            $properties = $vocabulary->properties();
            if (empty($properties)) {
                continue;
            }
            foreach ($properties as $property) {
                $result['names'][$property->term()] = $property;
                $name = $vocabulary->label() . ':' . $property->label();
                if (isset($result['labels'][$name])) {
                    $result['labels'][$vocabulary->label() . ':' . $property->label() . ' (#' . $property->id() . ')'] = $property;
                } else {
                    $result['labels'][$vocabulary->label() . ':' . $property->label()] = $property;
                }
            }
        }
        return $result;
    }

    /**
     * Trim and remove multiple spaces and no-break spaces (\u00A0 and \u202F)
     * that may be added automatically in some spreadsheets before or after ":"
     * or inadvertently and that may be hard to find.
     *
     * @param array $list
     * @return array
     */
    protected function cleanSpaces(array $list)
    {
        return array_map(function ($v) {
            return preg_replace(
                '~\s*:\s*~', ':', preg_replace(
                    '~\s\s+~', ' ', trim(str_replace(
                        [' ', ' '], ' ', $v
            ))));
        }, $list);
    }

    public function setConfigCsvImport(array $configCsvImport)
    {
        $this->configCsvImport = $configCsvImport;
    }
}
