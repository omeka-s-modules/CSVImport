<?php
namespace CSVImport\Source;

/*
 * Note: Reader isn’t traversable and has no rewind, so some hacks are required.
 * Nevertheless, the library is quick and efficient and the module uses it only
 * as recommended (as stream ahead).
 */
use Omeka\Stdlib\Message;
use OpenSpout\Reader\ReaderInterface;

abstract class AbstractSpreadsheet extends AbstractSource
{
    /**
     * @var string
     */
    protected $readerType;

    /**
     * @var ReaderInterface
     */
    protected $reader;

    /**
     * @var int
     */
    protected $position = 0;

    public function isValid()
    {
        if (!extension_loaded('zip') || !extension_loaded('xml')) {
            $this->errorMessage = new Message('To process import of a %s file, the php extensions "zip" and "xml" are required.', // @translate
                $this->readerType);
            return false;
        }

        return parent::isValid();
    }

    protected function checkNumberOfColumnsByRow()
    {
        $this->reset();
        $iterator = $this->getIterator();
        if (empty($iterator)) {
            return false;
        }

        $result = true;
        $headers = $this->getHeaders();
        $number = count($headers);
        /** @var \OpenSpout\Common\Entity\Row $row */
        foreach ($iterator as $row) {
            if ($row && $row->getNumCells() !== $number) {
                $result = false;
                break;
            }
        }

        $this->reset();
        return $result;
    }

    public function countRows()
    {
        // Reset is required because XmlReader is ahead only.
        $this->reset();
        $iterator = $this->getIterator();
        if (empty($iterator)) {
            return null;
        }
        $count = 0;
        foreach ($iterator as $row) {
            ++$count;
        }
        $this->reset();
        return $count;
    }

    public function getRows($offset = 0, $limit = -1)
    {
        if (empty($limit)) {
            return null;
        }

        if ($offset < $this->position) {
            $this->reset();
        }
        $iterator = $this->getIterator();
        if (empty($iterator) || !$iterator->valid()) {
            return null;
        }

        $rows = [];
        $count = 0;
        $rowOffset = $offset;
        $hasRows = false;
        while ($iterator->valid()) {
            // Account for the iterator being used without first being rewound
            // (OpenSpout returns null in this case against its own type
            // hint, causing a TypeError)
            try {
                /** @var \OpenSpout\Common\Entity\Row $row */
                $row = $iterator->current();
            } catch (\TypeError $e) {
                $iterator->rewind();
                continue;
            }
            if ($rowOffset == $this->position) {
                $rows[] = $this->cleanRow($row->toArray());
                ++$count;
                ++$rowOffset;
                $hasRows = true;
            }
            $this->position++;
            $iterator->next();
            if ($limit > 0 && $count >= $limit) {
                $hasRows = true;
                break;
            }
            if (!$iterator->valid()) {
                break;
            }
        }

        return $hasRows ? $rows : null;
    }

    public function clean()
    {
        $this->reset();
        return parent::clean();
    }

    protected function reset()
    {
        if ($this->reader) {
            $this->reader->close();
        }
        $this->reader = null;
        $this->position = 0;
        parent::reset();
    }

    protected function prepareIterator()
    {
        if ($this->reader) {
            $this->reset();
        }

        switch ($this->readerType) {
            case 'csv':
                $this->reader = new \OpenSpout\Reader\CSV\Reader();
                break;
            case 'ods':
                $options = new \OpenSpout\Reader\ODS\Options();
                $options->SHOULD_FORMAT_DATES = true;
                $this->reader = new \OpenSpout\Reader\ODS\Reader($options);
                break;
            default:
                throw new \LogicException((string) new Message(
                    'Unsupported spreadsheet reader type "%s".', // @translate
                    $this->readerType
                ));
        }
        try {
            $this->reader->open($this->source);
        } catch (\OpenSpout\Common\Exception\IOException $e) {
            return null;
        }

        foreach ($this->reader->getSheetIterator() as $sheet) {
            $this->iterator = $sheet->getRowIterator();
            break;
        }
        return $this->iterator;
    }
}
