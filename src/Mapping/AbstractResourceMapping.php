<?php
namespace CSVImport\Mapping;

use CSVImport\Mvc\Controller\Plugin\FindResourcesFromIdentifiers;
use Omeka\Stdlib\Message;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Renderer\PhpRenderer;

abstract class AbstractResourceMapping extends AbstractMapping
{
    protected $label;
    protected $name = 'resource-data';
    protected $resourceType;

    /**
     * @var FindResourcesFromIdentifiers
     */
    protected $findResourceFromIdentifier;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var array
     */
    protected $map;

    public function getSidebar(PhpRenderer $view)
    {
        return $view->partial('csv-import/mapping-sidebar/resource');
    }

    public function init(array $args, ServiceLocatorInterface $serviceLocator)
    {
        parent::init($args, $serviceLocator);
        $this->findResourceFromIdentifier = $serviceLocator->get('ControllerPluginManager')
            ->get('findResourceFromIdentifier');
    }

    public function processRow(array $row)
    {
        // Reset the data and the map between rows.
        $this->setHasErr(false);
        $this->data = [];
        $this->map = [];

        // First, pull in the global settings.
        $this->processGlobalArgs();

        $multivalueMap = isset($this->args['column-multivalue']) ? $this->args['column-multivalue'] : [];
        $multivalueSeparator = $this->args['multivalue_separator'];
        foreach ($row as $index => $values) {
            if (empty($multivalueMap[$index])) {
                $values = [$values];
            } else {
                $values = explode($multivalueSeparator, $values);
                $values = array_map(function ($v) {
                    return trim($v);
                }, $values);
            }
            $values = array_filter($values, 'strlen');
            if ($values) {
                $this->processCell($index, $values);
            }
        }

        return $this->data;
    }

    protected function processGlobalArgs()
    {
        $data = &$this->data;

        // Set columns.
        if (isset($this->args['column-resource_template'])) {
            $this->map['resourceTemplate'] = $this->args['column-resource_template'];
            $data['o:resource_template'] = null;
        }
        if (isset($this->args['column-resource_class'])) {
            $this->map['resourceClass'] = $this->args['column-resource_class'];
            $data['o:resource_class'] = null;
        }
        if (isset($this->args['column-owner_email'])) {
            $this->map['ownerEmail'] = $this->args['column-owner_email'];
            $data['o:owner'] = null;
        }
        if (isset($this->args['column-is_public'])) {
            $this->map['isPublic'] = $this->args['column-is_public'];
            $data['o:is_public'] = null;
        }

        // Set default values.
        if (!empty($this->args['o:resource_template']['o:id'])) {
            $data['o:resource_template'] = ['o:id' => (int) $this->args['o:resource_template']['o:id']];
        }
        if (!empty($this->args['o:resource_class']['o:id'])) {
            $data['o:resource_class'] = ['o:id' => (int) $this->args['o:resource_class']['o:id']];
        }
        if (!empty($this->args['o:owner']['o:id'])) {
            $data['o:owner'] = ['o:id' => (int) $this->args['o:owner']['o:id']];
        }
        if (isset($this->args['o:is_public']) && strlen($this->args['o:is_public'])) {
            $data['o:is_public'] = (bool) $this->args['o:is_public'];
        }
    }

    protected function processGlobalArgsItemSet()
    {
        $data = &$this->data;

        // Set columns.
        if (isset($this->args['column-is_open'])) {
            $this->map['isOpen'] = $this->args['column-is_open'];
            $data['o:is_open'] = null;
        }

        // Set default values.
        if (isset($this->args['o:is_open']) && strlen($this->args['o:is_open'])) {
            $data['o:is_open'] = (bool) $this->args['o:is_open'];
        }
    }

    protected function processGlobalArgsItem()
    {
        $data = &$this->data;
        $action = $this->args['action'];

        // Set columns.
        if (isset($this->args['column-item_set'])) {
            $this->map['itemSet'] = $this->args['column-item_set'];
            $data['o:item_set'] = [];
        }

        // Set default values.
        if (!empty($this->args['o:item_set'])) {
            $data['o:item_set'] = [];
            foreach ($this->args['o:item_set'] as $id) {
                $data['o:item_set'][] = ['o:id' => (int) $id];
            }
        }

        // Set site assignments
        if (!empty($this->args['o:site'])) {
            $data['o:site'] = [];
            foreach ($this->args['o:site'] as $id) {
                $data['o:site'][] = ['o:id' => (int) $id];
            }
        } elseif ($action === \CSVImport\Job\Import::ACTION_CREATE) {
            // Allow assignment of no sites when creating
            $data['o:site'] = [];
        }
    }

    protected function processGlobalArgsMedia()
    {
        $data = &$this->data;

        // Set columns.
        if (isset($this->args['column-item'])) {
            $this->map['item'] = $this->args['column-item'];
            $data['o:item'] = null;
        }
    }

    /**
     * Process the content of a cell (one csv value).
     *
     * @param int $index
     * @param array $values The content of the cell as an array (only one value
     * if the cell is not multivalued).
     */
    protected function processCell($index, array $values)
    {
        $data = &$this->data;

        if ($index === $this->args['identifier_column']) {
            $data['o-module-csv-import:resource-identifier'] = reset($values);
        }

        if (isset($this->map['resourceTemplate'][$index])) {
            $resourceTemplate = $this->findResourceTemplate(reset($values));
            if ($resourceTemplate) {
                $data['o:resource_template'] = ['o:id' => $resourceTemplate->id()];
            }
        }

        if (isset($this->map['resourceClass'][$index])) {
            $resourceClass = $this->findResourceClass(reset($values));
            if ($resourceClass) {
                $data['o:resource_class'] = ['o:id' => $resourceClass->id()];
            }
        }

        if (isset($this->map['ownerEmail'][$index])) {
            $user = $this->findUser(reset($values));
            if ($user) {
                $data['o:owner'] = ['o:id' => $user->id()];
            }
        }

        if (isset($this->map['isPublic'][$index])) {
            $value = reset($values);
            if (strlen($value)) {
                $data['o:is_public'] = in_array(strtolower($value), ['false', 'no', 'off', 'private'])
                    ? false
                    : (bool) $value;
            }
        }
    }

    protected function processCellItemSet($index, array $values)
    {
        $data = &$this->data;

        if (isset($this->map['isOpen'][$index])) {
            $value = reset($values);
            if (strlen($value)) {
                $data['o:is_open'] = in_array(strtolower($value), ['false', 'no', 'off', 'closed'])
                    ? false
                    : (bool) $value;
            }
        }
    }

    protected function processCellItem($index, array $values)
    {
        $data = &$this->data;

        if (isset($this->map['itemSet'][$index])) {
            $identifierProperty = $this->map['itemSet'][$index];
            $resourceType = 'item_sets';
            $findResourceFromIdentifier = $this->findResourceFromIdentifier;
            foreach ($values as $identifier) {
                $resourceId = $findResourceFromIdentifier($identifier, $identifierProperty, $resourceType);
                if ($resourceId) {
                    $data['o:item_set'][] = ['o:id' => $resourceId];
                } else {
                    $this->logger->err(new Message('"%s" (%s) is not a valid item set.', // @translate
                        $identifier, $identifierProperty));
                    $this->setHasErr(true);
                }
            }
        }
    }

    protected function processCellMedia($index, array $values)
    {
        $data = &$this->data;

        if (isset($this->map['item'][$index])) {
            // Check params to avoid useless search and improve speed.
            $action = $this->args['action'];
            $identifier = reset($values);
            $identifierProperty = $this->map['item'][$index] ?: 'internal_id';
            $resourceType = 'items';

            if (empty($identifier)) {
                // The parent identifier is needed only to create a media.
                if ($action === \CSVImport\Job\Import::ACTION_CREATE) {
                    $this->logger->err(new Message('An item identifier is required to process action "%s".', // @translate
                        $action));
                    $this->setHasErr(true);
                    return false;
                }
                return;
            }

            $findResourceFromIdentifier = $this->findResourceFromIdentifier;
            $resourceId = $findResourceFromIdentifier($identifier, $identifierProperty, $resourceType);
            if ($resourceId) {
                $data['o:item'] = ['o:id' => $resourceId];
            } else {
                $this->logger->err(new Message('"%s" (%s) is not a valid item identifier.', // @translate
                    $identifier, $identifierProperty));
                $this->setHasErr(true);
                return false;
            }
        }
    }

    protected function findResource($identifier, $identifierProperty = 'internal_id')
    {
        $resourceType = $this->args['resource_type'];
        $findResourceFromIdentifier = $this->findResourceFromIdentifier;
        $resourceId = $findResourceFromIdentifier($identifier, $identifierProperty, $resourceType);
        if (empty($resourceId)) {
            $this->logger->err(new Message('"%s" (%s) is not a valid resource identifier.', // @translate
                $identifier, $identifierProperty));
            $this->setHasErr(true);
            return false;
        }

        return $resourceId;
    }

    protected function findResourceTemplate($label)
    {
        $response = $this->api->search('resource_templates', ['label' => $label]);
        $content = $response->getContent();
        if (empty($content)) {
            $this->logger->err(new Message('"%s" is not a valid Resource Template name.', $label)); // @translate
            $this->setHasErr(true);
            return false;
        }
        $template = $content[0];
        $templateLabel = $template->label();
        if (strtolower($label) != strtolower($templateLabel)) {
            $this->logger->err(new Message('"%s" is not a valid Resource Template name.', $label)); // @translate
            $this->setHasErr(true);
            return false;
        }
        return $content[0];
    }

    protected function findResourceClass($term)
    {
        // TODO Allow to find a resource class by label.
        $response = $this->api->search('resource_classes', ['term' => $term]);
        $content = $response->getContent();
        if (empty($content)) {
            $message = new Message('"%s" is not a valid resource class. Resource Classes must be a Class found on the Vocabularies page.', // @translate;
                $term);
            $this->logger->err($message);
            $this->setHasErr(true);
            return false;
        }
        $class = $content[0];
        $classTerm = $class->term();
        if (strtolower($term) != strtolower($classTerm)) {
            $message = new Message('"%s" is not a valid resource class. Resource Classes must be a Class found on the Vocabularies page.', // @translate;
                $term);
            $this->logger->err($message);
            $this->setHasErr(true);
            return false;
        }
        return $content[0];
    }

    protected function findUser($email)
    {
        $response = $this->api->search('users', ['email' => $email]);
        $content = $response->getContent();
        if (empty($content)) {
            $this->logger->err(new Message('"%s" is not a valid user email address.', $email)); // @translate
            $this->setHasErr(true);
            return false;
        }
        $user = $content[0];
        $userEmail = $user->email();
        if (strtolower($email) != strtolower($userEmail)) {
            $this->logger->err(new Message('"%s" is not a valid user email address.', $email)); // @translate
            $this->setHasErr(true);
            return false;
        }
        return $content[0];
    }
}
