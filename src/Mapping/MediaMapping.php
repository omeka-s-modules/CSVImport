<?php
namespace CSVImport\Mapping;

use CSVImport\Job\Import;
use CSVImport\Mvc\Controller\Plugin\FindResourcesFromIdentifiers;
use Zend\View\Renderer\PhpRenderer;

class MediaMapping extends ResourceMapping
{
    /**
     * @var FindResourcesFromIdentifiers
     */
    protected $findResourceFromIdentifier;

    public static function getLabel()
    {
        return 'Media data'; // @translate
    }

    public static function getSidebar(PhpRenderer $view)
    {
        return $view->resourceSidebar('media');
    }

    protected function processGlobalArgs()
    {
        parent::processGlobalArgs();

        $data = &$this->data;

        $this->map['item'] = isset($this->args['column-items'])
            ? $this->args['column-items']
            : [];

        $this->findResourceFromIdentifier = $this->getServiceLocator()->get('ControllerPluginManager')
            ->get('findResourceFromIdentifier');
    }

    protected function processCell($index, array $values)
    {
        parent::processCell($index, $values);

        $data = &$this->data;

        if (isset($this->map['columnResourceIdentifier'][$index])) {
            // Check params to avoid useless search and improve speed.
            $action = &$this->args['action'];
            $resourceType = empty($this->map['columnResourceIdentifier'][$index]['type'])
                ? 'resources'
                : $this->map['columnResourceIdentifier'][$index]['type'];
            if ($action === Import::ACTION_CREATE) {
                if (!in_array($resourceType, ['resources', 'items'])) {
                    return;
                }
                $resourceType = 'items';
            } else {
                if (!in_array($resourceType, ['resources', 'media'])) {
                    return;
                }
                $resourceType = 'media';
            }

            $findResourceFromIdentifier = $this->findResourceFromIdentifier;
            $resourceId = $findResourceFromIdentifier(
                reset($values),
                $this->map['columnResourceIdentifier'][$index]['property'],
                $this->map['columnResourceIdentifier'][$index]['type']
            );
            if ($resourceId) {
                if ($this->args['action'] === Import::ACTION_CREATE) {
                    $data['o:item'] = ['o:id' => $resourceId];
                } else {
                    $data['o:id'] = $resourceId;
                }
            }
        }
    }
}
