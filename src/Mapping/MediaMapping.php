<?php
namespace CSVImport\Mapping;

use CSVImport\Job\Import;
use Zend\View\Renderer\PhpRenderer;

class MediaMapping extends ResourceMapping
{
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

        $this->map['item'] = isset($this->args['column-item'])
            ? $this->args['column-item']
            : [];
    }

    protected function processCell($index, array $values)
    {
        parent::processCell($index, $values);

        $data = &$this->data;

        if (isset($this->map['columnResourceIdentifier'][$index])) {
            // The parent identifier is needed only to create a media.
            $action = &$this->args['action'];
            if ($action !== Import::ACTION_CREATE) {
                return false;
            }

            // Check params to avoid useless search and improve speed.
            $identifier = reset($values);
            if (empty($identifier)) {
                $this->logger->err(sprintf('An item identifier is required to process action "%s".', // @translate
                    $action));
                $this->setHasErr(true);
                return false;
            }

            $identifierProperty = $this->map['columnResourceIdentifier'][$index]['property'];
            if (empty($identifierProperty)) {
                $identifierProperty = 'internal_id';
            }

            $resourceType = $this->map['columnResourceIdentifier'][$index]['type'];
            if (!empty($resourceType)) {
                if (!in_array($resourceType, ['resources', 'items'])) {
                    $this->logger->err(sprintf('"%s" is not a valid resource type to create a media.', // @translate
                        $resourceType));
                    $this->setHasErr(true);
                    return false;
                }
            }
            $resourceType = 'items';

            $findResourceFromIdentifier = $this->findResourceFromIdentifier;
            $resourceId = $findResourceFromIdentifier($identifier, $identifierProperty, $resourceType);
            if ($resourceId) {
                $data['o:item'] = ['o:id' => $resourceId];
            } else {
                $this->logger->err(sprintf('"%s" (%s) is not a valid item identifier.', // @translate
                    $identifier, $identifierProperty));
                $this->setHasErr(true);
                return false;
            }
        }
    }
}
