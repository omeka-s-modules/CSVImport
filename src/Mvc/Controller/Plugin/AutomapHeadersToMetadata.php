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

        // Prepare the list of labels one time to speed up process.
        $propertyByLabels = $this->listVocabularyAndTermLabels();

        foreach ($headers as $index => $header) {
            switch ($resourceType) {
                case 'item_sets':
                case 'items':
                case 'media':
                    // Check strict term name, like "dcterms:title".
                    if (preg_match('/^[a-z0-9_-]+:[a-z0-9_-]+$/i', $header)) {
                        $response = $api->search('properties', ['term' => $header]);
                        $content = $response->getContent();
                        if (!empty($content)) {
                            $property = reset($content);
                            $automaps[$index] = $property;
                            continue 2;
                        }
                    }
                    // Check vocabulary name and label ("Dublin Core : Title").
                    if (isset($propertyByLabels[$header])) {
                        $property = $propertyByLabels[$header];
                        $automaps[$index] = $property;
                        continue 2;
                    }
                    break;
                case 'users':
                    break;
            }
        }

        return $automaps;
    }

    /**
     * Return the list of properties by labels.
     *
     * @return array Associative array of vocabulary and term labels as keys
     * (ex: "Dublin Core : Title") and properties as value.
     * Note: Some terms are badly standardized (in foaf, the label "Given name"
     * matches "foaf:givenName" and "foaf:givenname"), so, in that case, keep
     * the first property.
     */
    protected function listVocabularyAndTermLabels()
    {
        $result = [];
        $vocabularies = $this->getController()->api()->search('vocabularies')->getContent();
        foreach ($vocabularies as $vocabulary) {
            $properties = $vocabulary->properties();
            if (empty($properties)) {
                continue;
            }
            foreach ($properties as $property) {
                $name = $vocabulary->label() .  ':' . $property->label();
                if (!isset($result[$name])) {
                    $result[$vocabulary->label() .  ':' . $property->label()] = $property;
                }
            }
        }
        return $result;
    }
}
