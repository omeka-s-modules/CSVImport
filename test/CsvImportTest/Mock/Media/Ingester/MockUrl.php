<?php
namespace CSVImportTest\Mock\Media\Ingester;

use Omeka\Api\Request;
use Omeka\Entity\Media;
use Omeka\File\TempFileFactory;
use Omeka\Media\Ingester\Url;
use Omeka\Stdlib\ErrorStore;

class MockUrl extends Url
{
    protected $tempFileFactory;

    public function setTempFileFactory(TempFileFactory $tempFileFactory)
    {
        $this->tempFileFactory = $tempFileFactory;
    }

    public function ingest(Media $media, Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();
        $uri = $data['ingest_url'];

        // Use normal ingester for remote url.
        if (strpos($uri, 'http://localhost/') !== 0) {
            parent::ingest($media, $request, $errorStore);
            return;
        }

        $uripath = realpath(str_replace('http://localhost/', __DIR__ . '/../../../../../../../', $uri));

        $tempFile = $this->tempFileFactory->build();
        $tempFile->setSourceName($uripath);

        $media->setStorageId($tempFile->getStorageId());
        $media->setExtension($tempFile->getExtension());
        $media->setMediaType($tempFile->getMediaType());
        $media->setSha256($tempFile->getSha256());
        // $hasThumbnails = $tempFile->storeThumbnails();
        $hasThumbnails = false;
        $media->setHasThumbnails($hasThumbnails);
        if (!array_key_exists('o:source', $data)) {
            $media->setSource($uri);
        }
        if (!isset($data['store_original']) || $data['store_original']) {
            $tempFile->storeOriginal();
            $media->setHasOriginal(true);
        }
        $tempFile->delete();
    }
}
