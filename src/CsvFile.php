<?php
namespace CSVImport;

use SplFileObject;
use Omeka\Service\Exception\ConfigException;

class CsvFile
{
    public $fileObject;

    public $tempPath;

    public $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function isUtf8()
    {
        $this->fileObject->rewind();
        $string = $this->fileObject->fgets();
        $isUtf8 = mb_detect_encoding($string, 'UTF-8', true);
        return $isUtf8 == 'UTF-8';
    }

    public function moveToTemp($systemTempPath)
    {
        move_uploaded_file($systemTempPath, $this->tempPath);
    }

    public function loadFromTempPath()
    {
        $tempPath = $this->getTempPath();
        $this->fileObject = new SplFileObject($tempPath);
        $this->fileObject->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);
    }

    public function getHeaders()
    {
        $this->fileObject->rewind();
        $line = $this->fileObject->fgetcsv();
        return $line;
    }

    /**
     * Get the path to the temporary file.
     *
     * @param null|string $tempDir
     * @return string
     */
    public function getTempPath($tempDir = null)
    {
        if (isset($this->tempPath)) {
            return $this->tempPath;
        }
        if (!isset($tempDir)) {
            $config = $this->config;
            if (!isset($config['temp_dir'])) {
                throw new ConfigException('Missing temporary directory configuration');
            }
            $tempDir = $config['temp_dir'];
        }
        $this->tempPath = tempnam($tempDir, 'omeka');
        return $this->tempPath;
    }

    /**
     * Use this to set the known (already-uploaded) csv file's path to where omekas puts it.
     */
    public function setTempPath($tempPath)
    {
        $this->tempPath = $tempPath;
    }

    /**
     * Delete this temporary file.
     *
     * Always delete a temporary file after all work has been done. Otherwise
     * the file will remain in the temporary directory.
     *
     * @return bool Whether the file was deleted/never created
     */
    public function delete()
    {
        if (isset($this->tempPath)) {
            return unlink($this->tempPath);
        }
        return true;
    }
}
