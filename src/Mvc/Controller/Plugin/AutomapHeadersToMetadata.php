<?php
namespace CSVImport\Mvc\Controller\Plugin;

use Omeka\Api\Manager as ApiManager;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\I18n\Translator\TranslatorAwareInterface;
use Zend\I18n\Translator\TranslatorAwareTrait;

class AutomapHeadersToMetadata extends AbstractPlugin implements TranslatorAwareInterface
{
    use TranslatorAwareTrait;

    /**
     * @var array
     */
    protected $configCsvImport;

    /**
     * @var ApiManager
     */
    protected $api;

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
     * - format (string) The type of result: may be "form", "arguments", or raw.
     * - mappings (array) The list of mappings associated to the resource type.
     * @return array Associative array of the index of the headers as key and
     * the matching metadata as value. Only mapped headers are set.
     */
    public function __invoke(array $headers, $resourceType = null, array $options = null)
    {
        $automaps = [];

        // Set the default automapping from the config if not set.
        if (!isset($options['automap_list'])) {
            $options['automap_list'] = $this->configCsvImport['user_settings']['csvimport_automap_user_list'];
        }

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

        $mappings = isset($options['mappings']) ? $options['mappings'] : [];

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

            // Check strict term name, like "dcterms:title", sensitively then
            // insensitively, then term label like "Dublin Core : Title"
            // sensitively then insensitively too. Because all the lists
            // contains the same keys in the same order, the process can be done
            // in one step.
            if (in_array(\CSVImport\Mapping\PropertyMapping::class, $mappings)) {
                foreach ($lists as $listName => $list) {
                    $toSearch = strpos($listName, 'lower_') === 0 ? $lowerHeader : $header;
                    $found = array_search($toSearch, $list, true);
                    if ($found) {
                        $property = $propertyLists['names'][$found];
                        $automaps[$index] = $property;
                        continue 2;
                    }
                }
            }
        }

        if (empty($options['format']) || !in_array($options['format'], ['form', 'arguments'])) {
            return $automaps;
        }
        /*
        return $options['format'] === 'form'
            ? $this->normalizeAutomapsForForm($automaps, $resourceType)
            :  $this->normalizeAutomapsAsArguments($automaps, $resourceType);
        */

        return $this->normalizeAutomapsForForm($automaps, $resourceType);
    }

    /**
     * Prepare automaps to be used in a form, and filter it with resource type.
     *
     * @todo Automapping are not filtered by resource type currently.
     *
     * @param array $automaps
     * @param string $resourceType
     * @return array
     */
    protected function normalizeAutomapsForForm(array $automaps, $resourceType)
    {
        $result = [];
        $automapping = empty($this->options['automap_list'])
            ? []
            : $this->prepareAutomapping();
        foreach ($automaps as $index => $automap) {
            if (is_object($automap)) {
                if ($automap->getJsonLdType() === 'o:Property') {
                    $value = [];
                    $value['name'] = 'property';
                    $value['value'] = $automap->id();
                    $value['label'] = $this->getTranslator()->translate($automap->label());
                    $value['class'] = 'property';
                    $value['special'] = ' data-property-id="' . $automap->id(). '"';
                    $value['multiple'] = true;
                    $result[$index] = $value;
                }
            } elseif (is_string($automap)) {
                // Get the options of the automap.
                $check = preg_match('~^(.+?)\s*(\{.+\})$~', $automap, $matches);
                if ($check) {
                    if (isset($automapping[$matches[1]])) {
                        $value = $automapping[$matches[1]];
                        $multipleOptions = @json_decode($matches[2], true);
                        $value['value'] = $multipleOptions
                            ? $matches[2]
                            : trim($matches[2], "\t\r\n {}");
                        $value['label'] = $multipleOptions
                            ? vsprintf($value['label'], $multipleOptions)
                            : sprintf($value['label'], $value['value']);
                        $result[$index] = $value;
                    }
                } elseif (isset($automapping[$automap])) {
                    $value = $automapping[$automap];
                    if ($value !== 1) {
                        $value['label'] = is_array($value['value'])
                            ? vsprintf($value['label'], $value['value'])
                            : sprintf($value['label'], $value['value']);
                        $result[$index] = $value;
                    } else {
                        $result[$index] = $value;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Prepare automaps as import arguments, and filter it with resource type.
     *
     * @todo Automapping are not filtered by resource type currently.
     *
     * @param array $automaps
     * @param string $resourceType
     * @return array
     */
    /*
    protected function normalizeAutomapsAsArguments(array $automaps, $resourceType)
    {
        $result = [];
        $automapping = empty($this->options['automap_list'])
            ? []
            : $this->prepareAutomapping();
        foreach ($automaps as $index => $automap) {
            if (is_object($automap)) {
                if ($automap->getJsonLdType() === 'o:Property') {
                    $result['column-property'][$index][$automap->term()] = $automap->id();
                }
            } elseif (is_string($automap)) {
                // Get the options of the automap.
                $check = preg_match('~^(.+?)\s*(\{.+\})$~', $automap, $matches);
                if ($check) {
                    if (isset($automapping[$matches[1]])) {
                        $name = $automapping[$matches[1]]['name'];
                        $value = @json_decode($matches[2], true) ?: trim($matches[2], "\t\r\n {}");
                    }
                } elseif (isset($automapping[$automap])) {
                    $name = $automapping[$automap]['name'];
                    $value = $automapping[$automap]['value'];
                } else {
                    $name = null;
                    continue;
                }
                $result['column-' . $name][$index] = $value;
            }
        }
        return $result;
    }
*/
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
        $vocabularies = $this->api->search('vocabularies')->getContent();
        foreach ($vocabularies as $vocabulary) {
            $properties = $vocabulary->properties();
            if (empty($properties)) {
                continue;
            }
            foreach ($properties as $property) {
                $result['names'][$property->term()] = $property;
                $name = $vocabulary->label() .  ':' . $property->label();
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

    /**
     * Clean the automap list to format to remove old properties.
     *
     * @param array $automapList
     * @param array $propertyList
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
        $translator = $this->getTranslator();

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
                'name' => 'owner_email',
                'label' => $translator->translate('Owner email address'),
                'class' => 'owner-email',
            ],
            'internal_id' => [
                'name' => 'resource',
                'value' => 'internal_id',
                'label' => $translator->translate('Internal id'),
                'class' => 'resource-data',
            ],
            'resource' => [
                'name' => 'resource',
                'value' => 'internal_id',
                'label' => $translator->translate('Resource [%s]'),
                'class' => 'resource-data',
            ],
            'resource_type' => [
                'name' => 'resource_type',
                'label' => $translator->translate('Resource type'),
                'class' => 'resource-data',
            ],
            'resource_template' => [
                'name' => 'resource_template',
                'label' => $translator->translate('Resource template name'),
                'class' => 'resource-data',
            ],
            'resource_class' => [
                'name' => 'resource_class',
                'label' => $translator->translate('Resource class term'),
                'class' => 'resource-data',
            ],
            'is_public' => [
                'name' => 'is_public',
                'label' => $translator->translate('Visibility public/private'),
                'class' => 'resource-data',
            ],
            'is_open' => [
                'name' => 'is_open',
                'label' => $translator->translate('Additions open/closed'),
                'class' => 'resource-data',
            ],
            'item_set' => [
                'name' => 'item_set',
                'value' => 'internal_id',
                'label' => $translator->translate('Item set [%s]'),
                'class' => 'resource-data',
            ],
            'item' => [
                'name' => 'item',
                'value' => 'internal_id',
                'label' => $translator->translate('Item [%s]'),
                'class' => 'resource-data',
            ],
            'media' => [
                'name' => 'media',
                'value' => 'internal_id',
                'label' => $translator->translate('Media [%s]'),
                'class' => 'resource-data',
            ],
            'media_source' => [
                'name' => 'media_source',
                'value' => null,
                'label' => $translator->translate('Media source (%s)'),
                'class' => 'media-source',
            ],
            'user_name' => [
                'name' => 'user_name',
                'label' => $translator->translate('Display name'),
                'class' => 'user-name',
            ],
            'user_email' => [
                'name' => 'user_email',
                'label' => $translator->translate('Email'),
                'class' => 'user-email',
            ],
            'user_role' => [
                'name' => 'user_role',
                'label' => $translator->translate('Role'),
                'class' => 'user-role',
            ],
            'user_is_active' => [
                'name' => 'user-is-active',
                'label' => $translator->translate('User is active'),
                'class' => 'user-is-active',
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

    public function setApiManager(ApiManager $apiManager)
    {
        $this->api = $apiManager;
    }
}
