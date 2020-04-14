<?php
namespace CSVImport\Source;

use SplFileObject;

/**
 * @link https://www.iana.org/assignments/media-types/text/tab-separated-values
 */
class TsvFile extends CsvFile
{
    /**
     * Default delimiter of tsv.
     *
     * @var string
     */
    const DEFAULT_DELIMITER = "\t";

    /**
     * Default enclosure of tsv.
     *
     * @var string
     */
    const DEFAULT_ENCLOSURE = '';

    /**
     * Default escape of tsv.
     *
     * @var string
     */
    const DEFAULT_ESCAPE = '';

    protected $mediaType = 'text/tab-separated-values';

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
        // No parameter, according to standard. If needed, use the csv format.
    }

    public function setDelimiter($delimiter)
    {
        return;
    }

    public function setEnclosure($enclosure)
    {
        return;
    }

    public function setEscape($escape)
    {
        return;
    }

    protected function setIteratorParams()
    {
        if ($this->iterator) {
            $this->iterator->setFlags(SplFileObject::READ_CSV | SplFileObject::READ_AHEAD
                | SplFileObject::SKIP_EMPTY);
            $this->iterator->setCsvControl($this->delimiter);
        }
    }
}
