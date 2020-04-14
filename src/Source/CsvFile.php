<?php
namespace CSVImport\Source;

use LimitIterator;
use SplFileObject;

class CsvFile extends AbstractSource
{
    /**
     * Default delimiter of csv.
     *
     * @var string
     */
    const DEFAULT_DELIMITER = ',';

    /**
     * Default enclosure of csv.
     *
     * @var string
     */
    const DEFAULT_ENCLOSURE = '"';

    /**
     * Default escape of csv.
     *
     * @var string
     */
    const DEFAULT_ESCAPE = '\\';

    protected $mediaType = 'text/csv';

    /**
     * Csv delimiter of the file (default to standard).
     *
     * @var string
     */
    protected $delimiter = self::DEFAULT_DELIMITER;

    /**
     * Csv enclosure of the file (default to standard).
     *
     * @var string
     */
    protected $enclosure = self::DEFAULT_ENCLOSURE;

    /**
     * Csv escape of the file (default to standard).
     *
     * @var string
     */
    protected $escape = self::DEFAULT_ESCAPE;

    public function setParameters(array $params)
    {
        if (isset($params['delimiter']) && strlen($params['delimiter'])) {
            $this->setDelimiter($params['delimiter']);
        }
        if (isset($params['enclosure']) && strlen($params['enclosure'])) {
            $this->setEnclosure($params['enclosure']);
        }
        if (isset($params['escape']) && strlen($params['escape'])) {
            $this->setEscape($params['escape']);
        }
        $this->reset();
    }

    public function getParameters()
    {
        return [
            'delimiter' => $this->delimiter,
            'enclosure' => $this->enclosure,
            'escape' => $this->escape,
        ];
    }

    public function isValid()
    {
        if (!parent::isValid()) {
            return false;
        }

        $result = $this->isUtf8();
        if (!$result) {
            $this->errorMessage = 'File is not UTF-8 encoded.'; // @translate
        }

        return $result;
    }

    /**
     * Check if the file is utf-8 formatted.
     *
     * @return bool
     */
    public function isUtf8()
    {
        $iterator = $this->getIterator();
        if (empty($iterator)) {
            return false;
        }

        $result = true;
        // TODO Use another check when mb is not installed.
        if (!function_exists('mb_detect_encoding')) {
            return true;
        }

        // Check all the file, because the headers are generally ascii.
        // Nevertheless, check the lines one by one as text to avoid a memory
        // overflow with a big csv file.
        $iterator->setFlags(0);

        $iterator->rewind();
        foreach (new LimitIterator($iterator) as $line) {
            if (mb_detect_encoding($line, 'UTF-8', true) !== 'UTF-8') {
                $result = false;
                break;
            }
        }

        $this->setIteratorParams();

        return $result;
    }

    protected function checkNumberOfColumnsByRow()
    {
        $iterator = $this->getIterator();
        if (empty($iterator)) {
            return false;
        }

        $result = true;
        $headers = $this->getHeaders();
        $number = count($headers);
        foreach (new LimitIterator($iterator) as $row) {
            if ($row && count($row) !== $number) {
                $result = false;
                break;
            }
        }

        return $result;
    }

    public function setDelimiter($delimiter)
    {
        $this->delimiter = $delimiter;
        $this->reset();
    }

    public function getDelimiter()
    {
        return $this->delimiter;
    }

    public function setEnclosure($enclosure)
    {
        $this->enclosure = $enclosure;
        $this->reset();
    }

    public function getEnclosure()
    {
        return $this->enclosure;
    }

    public function setEscape($escape)
    {
        $this->escape = $escape;
        $this->reset();
    }

    public function getEscape()
    {
        return $this->escape;
    }

    protected function prepareIterator()
    {
        $this->iterator = new SplFileObject($this->source);
        $this->setIteratorParams();
        return $this->iterator;
    }

    protected function setIteratorParams()
    {
        if ($this->iterator) {
            $this->iterator->setFlags(SplFileObject::READ_CSV | SplFileObject::READ_AHEAD
                | SplFileObject::SKIP_EMPTY);
            $this->iterator->setCsvControl($this->delimiter, $this->enclosure, $this->escape);
        }
    }
}
