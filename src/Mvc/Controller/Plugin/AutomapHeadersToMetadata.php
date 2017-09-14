<?php
namespace CSVImport\Mvc\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;

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
     * - automap_list (array) An associative array containing specific mappings.
     * - normalize (boolean) The type of result: for the form or for automatic.
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

        // Check automapping first.
        $automapList = empty($options['automap_list']) ? [] : $options['automap_list'];
        if ($automapList) {
            $automapList = $this->checkAutomapList($automapList, $propertyLists['names']);
            $automapLists['base'] = array_combine(
                array_keys($automapList),
                array_keys($automapList));
            $automapLists['lower_base'] = array_map('strtolower', $automapLists['base']);
            if ($automapLists['base'] === $automapLists['lower_base']) {
                unset($automapLists['base']);
            }
        }

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

        return empty($options['normalize'])
            ? $automaps
            : $this->normalizeAutomaps($automaps, $resourceType);
    }

    /**
     * Prepare automaps to be used in a form, and filter it with resource type.
     *
     * @param array $automaps
     * @param $resourceType
     * @return array
     */
    protected function normalizeAutomaps(array $automaps, $resourceType)
    {
        $result = [];
        $controller = $this->getController();
        $automapping = empty($this->options['automap_list'])
            ? []
            : $this->prepareAutomapping();
        foreach ($automaps as $index => $automap) {
            if (is_object($automap)) {
                if ($automap->getJsonLdType() === 'o:Property') {
                    $value = [];
                    $value['name'] = 'property';
                    $value['value'] = $automap->id();
                    $value['label'] = $controller->translate($automap->label());
                    $value['class'] = 'property';
                    $value['special'] = ' data-property-id="' . $automap->id(). '"';
                    $value['multiple'] = true;
                    $result[$index] = $value;
                }
            } elseif (is_string($automap)) {
                if (isset($automapping[$automap])) {
                    $result[$index] = $automapping[$automap];
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

    /**
     * Clean the automap list to format to remove old properties.
     *
     * @param unknown $automapList
     * @param unknown $propertyList
     * @return array
     */
    protected function checkAutomapList($automapList, $propertyList)
    {
        $result = $automapList;
        foreach ($automapList as $name => $value) {
            if (empty($value)) {
                unset($result[$name]);
                continue;
            }
            $isProperty = preg_match('/^[a-z0-9_-]+:[a-z0-9_-]+$/i', $value);
            if ($isProperty) {
                if (empty($propertyList[$value])) {
                    unset($result[$name]);
                } else {
                    $result[$name] = $propertyList[$value];
                }
            }
        }
        return $result;
    }

    /**
     * Define the list of common automapping and prepare it.
     *
     * @return array
     */
    protected function prepareAutomapping()
    {
        $controller = $this->getController();

        $defaultAutomapping = [
            'name' => '',
            'value' => 1,
            'label' => '' ,
            'class' => '',
            'special' => '',
            'multiple' => false,
        ];

        $automapping = [
            'owner_email' => [
                'name' => 'owneremail',
                'label' => $controller->translate('Owner email address'),
                'class' => 'owneremail',
            ],
            'resource_template' => [
                'name' => 'resourcetemplate',
                'label' => $controller->translate('Resource template name'),
                'class' => 'item-data',
            ],
            'resource_class' => [
                'name' => 'resourceclass',
                'label' => $controller->translate('Resource class term'),
                'class' => 'item-data',
            ],
            'item_sets' => [
                'name' => 'itemset-id',
                'label' => $controller->translate('Item set id'),
                'class' => 'item-data',
            ],
            'media_url' => [
                'name' => 'media',
                'value' => 'url',
                'label' => $controller->translate('URL'),
                'class' => 'media',
            ],
            'media_html' => [
                'name' => 'media',
                'value' => 'html',
                'label' => $controller->translate('HTML'),
                'class' => 'media',
            ],
            'media_iiif' => [
                'name' => 'media',
                'value' => 'iiif',
                'label' => $controller->translate('IIIF image'),
                'class' => 'media',
            ],
            'media_oEmbed' => [
                'name' => 'media',
                'value' => 'oembed',
                'label' => $controller->translate('oEmbed'),
                'class' => 'media',
            ],
            'media_youtube' => [
                'name' => 'media',
                'value' => 'youtube',
                'label' => $controller->translate('Youtube'),
                'class' => 'media',
            ],
            'user_name' => [
                'name' => 'user-displayname',
                'label' => $controller->translate('Display name'),
                'class' => 'user-displayname',
            ],
            'user_email' => [
                'name' => 'user-email',
                'label' => $controller->translate('Email'),
                'class' => 'user-email',
            ],
            'user_role' => [
                'name' => 'user-role',
                'label' => $controller->translate('Role'),
                'class' => 'user-role',
            ],
        ];

        $configAutomapping = $this->configCsvImport['automapping'];
        $automapping = array_merge_recursive($automapping, $configAutomapping);

        foreach ($automapping as &$value) {
            $value += $defaultAutomapping;
        }

        return $automapping;
    }

    public function setConfigCsvImport(array $configCsvImport)
    {
        $this->configCsvImport = $configCsvImport;
    }
}
