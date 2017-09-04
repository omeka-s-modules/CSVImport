<?php
namespace CSVImport;

use LimitIterator;
use Omeka\Service\Exception\ConfigException;
use SplFileObject;

class CsvFile
{
    /**
     * @var SplFileObject
     */
    public $fileObject;

    /**
     * @var string
     */
    public $tempPath;

    /**
     * @var array
     */
    public $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Check if the file is utf-8 formatted.
     *
     * @return bool
     */
    public function isUtf8()
    {
        $result = true;
        // Check all the file, because the headers are generally ascii.
        // Nevertheless, check the lines one by one as text to avoid a memory
        // overflow with a big csv file.
        $this->fileObject->setFlags(0);
        $this->fileObject->rewind();
        foreach (new LimitIterator($this->fileObject) as $line) {
            if (mb_detect_encoding($line, 'UTF-8', true) !== 'UTF-8') {
                $result = false;
                break;
            }
        }
        $this->fileObject->setFlags(SplFileObject::READ_CSV | SplFileObject::READ_AHEAD
            | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);
        return $result;
    }

    public function moveToTemp($systemTempPath)
    {
        move_uploaded_file($systemTempPath, $this->tempPath);
    }

    public function loadFromTempPath()
    {
        $tempPath = $this->getTempPath();
        $this->fileObject = new SplFileObject($tempPath);
        $this->fileObject->setFlags(SplFileObject::READ_CSV | SplFileObject::READ_AHEAD
            | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);
    }

    public function getHeaders()
    {
        $this->fileObject->rewind();
        $line = $this->fileObject->current();
        return array_map('trim', $line);
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
     * Return the number of non-empty rows.
     *
     * @return int
     */
    public function countRows()
    {
        // FileObject has no countRows() method, so count them one by one.
        // $file->key() + 1 cannot be used, because we want non-empty rows only.
        $file = $this->fileObject;
        $file->rewind();
        $index = 0;
        while ($file->valid()) {
            ++$index;
            $file->next();
        }
        return $index;
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
