<?php
namespace CSVImport\View\Helper;

use Zend\View\Helper\AbstractHelper;

class MediaSidebar extends AbstractHelper
{
    protected $mediaIngester;

    protected $mediaAdapters;

    protected $translator;

    public function __construct($mediaIngestManager, $mediaAdapters, $translator)
    {
        $this->mediaAdapters = $mediaAdapters;
        $this->mediaIngester = $mediaIngestManager;
        $this->translator = $translator;
    }

    public function __invoke()
    {
        $mediaForms = [];
        foreach ($this->mediaIngester->getRegisteredNames() as $ingester) {
            if (array_key_exists($ingester, $this->mediaAdapters)) {
                $mediaForms[$ingester] = [
                    'label' => $this->translator->translate($this->mediaIngester->get($ingester)->getLabel()),
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
