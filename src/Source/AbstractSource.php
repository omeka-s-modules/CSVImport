<?php
namespace CSVImport\Source;

use Iterator;
use LimitIterator;

abstract class AbstractSource implements SourceInterface
{
    /**
     * The media type processed by this class.
     *
     * @var string
     */
    protected $mediaType;

    /**
     * @var string
     */
    protected $source;

    /**
     * @var array
     */
    protected $params = [];

    /**
     * @var Iterator
     */
    protected $iterator;

    /**
     * @var string
     */
    protected $errorMessage;

    /**
     * @var array
     */
    protected $config;

    public function init(array $config)
    {
        $this->config = $config;
        $this->source = null;
        $this->params = [];
        $this->reset();
    }

    public function getMediaType()
    {
        return $this->mediaType;
    }

    public function setSource($source)
    {
        $this->source = $source;
        $this->reset();
    }

    public function setParameters(array $params)
    {
        $this->params = $params;
        $this->reset();
    }

    public function getParameters()
    {
        return $this->params;
    }

    public function isValid()
    {
        if (empty($this->source)) {
            $this->errorMessage = 'The source is undefined.'; // @translate
            return false;
        }

        if (!filesize($this->source)) {
            $this->errorMessage = 'The source is empty.'; // @translate
            return false;
        }

        $iterator = $this->getIterator();
        if (!$iterator) {
            $this->errorMessage = 'No file to process.'; // @translate
            return false;
        }

        // Check if all rows have the same number of columns.
        $result = $this->checkNumberOfColumnsByRow();
        if (!$result) {
            $this->errorMessage = 'The rows are not all the same number of columns.'; // @translate
            return false;
        }

        $this->errorMessage = '';
        return true;
    }

    protected function checkNumberOfColumnsByRow()
    {
        return true;
    }

    public function countRows()
    {
        $iterator = $this->getIterator();
        if (empty($iterator)) {
            return;
        }
        return iterator_count($iterator);
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    public function getHeaders()
    {
        $headers = $this->getRow(0);
        if (!is_array($headers)) {
            return;
        }

        // Remove last empty headers.
        $headers = array_reverse($headers, true);
        foreach ($headers as $key => $header) {
            if (strlen(trim($header))) {
                break;
            }
            unset($headers[$key]);
        }
        $headers = array_reverse($headers, true);

        // Strip UTF-8 encoded BOM, if present, in first header
        foreach ($headers as $key => $header) {
            if (substr($header, 0, 3) === "\xef\xbb\xbf") {
                $headers[$key] = substr($header, 3);
            }
            break;
        }

        return $headers;
    }

    public function getRow($offset)
    {
        $rows = $this->getRows($offset, 1);
        if (is_array($rows)) {
            return reset($rows);
        }
    }

    public function getRows($offset = 0, $limit = -1)
    {
        $iterator = $this->getIterator();
        if (empty($iterator)) {
            return;
        }
        if ($offset >= iterator_count($this->iterator)) {
            return;
        }

        $rows = [];
        $limitIterator = new LimitIterator($iterator, $offset, $limit);
        foreach ($limitIterator as $row) {
            $rows[] = $this->cleanRow($row);
        }
        return $rows;
    }

    protected function cleanRow(array $row)
    {
        return array_map(function ($v) {
            return trim($v);
        }, $row);
    }

    public function clean()
    {
        if (!empty($this->source) && file_exists($this->source) && is_writeable($this->source)) {
            return unlink($this->source);
        }
        $this->reset();
        return true;
    }

    protected function getIterator()
    {
        if ($this->iterator) {
            return $this->iterator;
        }
        if (empty($this->source)) {
            return;
        }
        return $this->prepareIterator();
    }

    protected function reset()
    {
        $this->iterator = null;
        $this->errorMessage = null;
    }

    /**
     * return Iterator
     */
    abstract protected function prepareIterator();
}
