<?php
namespace CSVImport\Mapping;

use CSVImport\Mvc\Controller\Plugin\FindResourcesFromIdentifiers;
use Zend\View\Renderer\PhpRenderer;

class ResourceMapping extends AbstractMapping
{
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

    public static function getLabel()
    {
        return "Resource data"; // @translate
    }

    public static function getName()
    {
        return 'resource-data';
    }

    public static function getSidebar(PhpRenderer $view)
    {
        return $view->resourceSidebar();
    }

    public function processRow(array $row)
    {
        $this->data = [];

        $this->findResourceFromIdentifier = $this->getServiceLocator()->get('ControllerPluginManager')
            ->get('findResourceFromIdentifier');

        // First, pull in the global settings.
        $this->processGlobalArgs();

        $multivalueMap = isset($this->args['column-multivalue']) ? $this->args['column-multivalue'] : [];
        $multivalueSeparator = $this->args['multivalue_separator'];
        foreach ($row as $index => $values) {
            if (empty($multivalueMap[$index])) {
                $values = [$values];
            } else {
                $values = explode($multivalueSeparator, $values);
                $values = array_map('trim', $values);
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
        if (isset($this->args['column-resource'])) {
            $this->map['resource'] = $this->args['column-resource'];
            $data['o:id'] = null;
        }
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
            $data['o:resource_class'] = ['o:id' => (int) $this->args['o:resource_class']['o:id']];;
        }
        if (!empty($this->args['o:owner']['o:id'])) {
            $data['o:owner'] = ['o:id' => (int) $this->args['o:owner']['o:id']];
        }
        if (isset($this->args['o:is_public']) && strlen($this->args['o:is_public'])) {
            $data['o:is_public'] = (int) (bool) $this->args['o:is_public'];
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

        if (isset($this->map['resource'][$index])) {
            $identifier = reset($values);
            $identifierProperty = $this->map['resource'][$index] ?: 'internal_id';
            $resourceId = $this->findResource($identifier, $identifierProperty);
            if ($resourceId) {
                $data['o:id'] = $resourceId;
            }
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
                $data['o:is_public'] = in_array(strtolower($value), ['false', 'off', 'private'])
                    ? 0
                    : (int) (bool) $value;
            }
        }
    }

    protected function findResource($identifier, $identifierProperty = 'internal_id')
    {
        $resourceType = $this->args['resource_type'];
        $findResourceFromIdentifier = $this->findResourceFromIdentifier;
        $resourceId = $findResourceFromIdentifier($identifier, $identifierProperty, $resourceType);
        if (empty($resourceId)) {
            $this->logger->err(sprintf('"%s" (%s) is not a valid resource identifier.', // @translate
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
            $this->logger->err(sprintf('"%s" is not a valid Resource Template name.', $label)); // @translate
            $this->setHasErr(true);
            return false;
        }
        $template = $content[0];
        $templateLabel = $template->label();
        if ($label != $templateLabel) {
            $this->logger->err(sprintf('"%s" is not a valid Resource Template name.', $label)); // @translate
            $this->setHasErr(true);
            return false;
        }
        return $content[0];
    }

    protected function findResourceClass($term)
    {
        $response = $this->api->search('resource_classes', ['term' => $term]);
        $content = $response->getContent();
        if (empty($content)) {
            $this->logger->err(sprintf('"%s" is not a valid resource class.', $term) // @translate
                . ' ' . 'Resource Classes must be a Class found on the Vocabularies page.'); // @translate
            $this->setHasErr(true);
            return false;
        }
        $class = $content[0];
        $classTerm = $class->term();
        if ($term != $classTerm) {
            $this->logger->err(sprintf('"%s" is not a valid resource class.', $term) // @translate
                . ' ' . 'Resource Classes must be a Class found on the Vocabularies page.'); // @translate
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
            $this->logger->err(sprintf('"%s" is not a valid user email address.', $email)); // @translate
            $this->setHasErr(true);
            return false;
        }
        $user = $content[0];
        $userEmail = $user->email();
        if ($email != $userEmail) {
            $this->logger->err(sprintf('"%s" is not a valid user email address.', $email)); // @translate
            $this->setHasErr(true);
            return false;
        }
        return $content[0];
    }
}
