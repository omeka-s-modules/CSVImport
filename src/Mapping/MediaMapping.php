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

        if (isset($this->map['item'][$index])) {
            // Check params to avoid useless search and improve speed.
            $action = $this->args['action'];
            $identifier = reset($values);
            $identifierProperty = $this->map['item'][$index] ?: 'internal_id';
            $resourceType = 'items';

            if (empty($identifier)) {
                // The parent identifier is needed only to create a media.
                if ($action === Import::ACTION_CREATE) {
                    $this->logger->err(sprintf('An item identifier is required to process action "%s".', // @translate
                        $action));
                    $this->setHasErr(true);
                    return false;
                }
            }

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
