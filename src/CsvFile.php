<?php
namespace CSVImport;

use SplFileObject;
use Zend\ServiceManager\ServiceLocatorInterface;

class CsvFile
{
    public $fileObject;

    public $tempPath;
    
    protected $serviceLocator;

    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
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
            $config = $this->serviceLocator->get('Config');
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