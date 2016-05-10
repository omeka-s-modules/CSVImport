<?php
namespace CSVImport\View\Helper;

use Zend\View\Helper\AbstractHelper;

class MediaSidebar extends AbstractHelper
{

    protected $mediaIngester;

    public function __construct($mediaIngestManager)
    {
        $this->mediaIngester = $mediaIngestManager;
    }

    public function __invoke()
    {
        $mediaForms = [];
        foreach ($this->mediaIngester->getRegisteredNames() as $ingester) {
            if ($ingester != 'upload') {
                $mediaForms[$ingester] = [
                    'label' => $this->mediaIngester->get($ingester)->getLabel(),
                ];
            }
        }

        return $this->getView()->partial(
            'common/media-sidebar',
            [
                'mediaForms' => $mediaForms,
            ]
        );
    }
}
