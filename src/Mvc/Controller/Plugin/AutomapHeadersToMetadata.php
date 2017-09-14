<?php
namespace CSVImport\Mvc\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class AutomapHeadersToMetadata extends AbstractPlugin
{
    /**
     * Automap a list of headers to a list of metadata.
     *
     * @param array $headers
     * @param string $resourceType
     * @return array Associative array of the index of the headers as key and
     * the matching metadata as value. Only mapped headers are set.
     */
    public function __invoke(array $headers, $resourceType = null)
    {
        $automaps = [];

        $api = $this->getController()->api();

        // Trim and remove multiple spaces and no-break spaces (\u00A0 and \u202F)
        // that may be added automatically in some spreadsheets before or after
        // ":" or inadvertently and that may be hard to find.
        $headers = array_map(function ($v) {
            return preg_replace(
                '~\s*:\s*~', ':', preg_replace(
                    '~\s\s+~', ' ', trim(str_replace(
                        [' ', ' '], ' ', $v
            ))));
        }, $headers);

        // Prepare the standard lists to check against.
        $lists = [];

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

        foreach ($headers as $index => $header) {
            $lowerHeader = strtolower($header);
            switch ($resourceType) {
                case 'item_sets':
                case 'items':
                case 'media':
                    // Check strict term name, like "dcterms:title", sensitively
                    // then insensitively, then term label like "Dublin Core : Title"
                    // sensitively then insensitively too. Because all the lists
                    // contains the same keys in the same order, the process can
                    // be done in one step.
                    foreach ($lists as $listName => $list) {
                        $toSearch = strpos('lower_', $listName) === 0 ? $lowerHeader : $header;
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

        return $automaps;
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
                $name = $vocabulary->label() .  ':' . $property->label();
                if (isset($result['labels'][$name])) {
                    $result['labels'][$vocabulary->label() .  ':' . $property->label() . ' (#' . $property->id() . ')'] = $property;
                } else {
                    $result['labels'][$vocabulary->label() .  ':' . $property->label()] = $property;
                }
            }
        }
        return $result;
    }
}
