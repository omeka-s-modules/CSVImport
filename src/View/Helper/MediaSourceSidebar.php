<?php
namespace CSVImport\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class MediaSourceSidebar extends AbstractHelper
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
        ksort($mediaForms);

        return $this->getView()->partial(
            'csv-import/mapping-sidebar/media-source',
            [
                'mediaForms' => $mediaForms,
            ]
        );
    }
}
