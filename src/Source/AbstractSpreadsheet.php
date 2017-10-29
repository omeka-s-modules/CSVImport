<?php
namespace CSVImport\Source;

/**
 * Note: Reader isn’t traversable and has no rewind, so some hacks are required.
 * Nevertheless, the library is quick and efficient and the module uses it only
 * as recommended (as stream ahead).
 */
use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Reader\ReaderInterface;

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

    public function countRows()
    {
        $this->reset();
        $iterator = $this->getIterator();
        if (empty($iterator)) {
            return;
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
            return;
        }
        if ($offset < $this->position) {
            $this->reset();
        }
        $iterator = $this->getIterator();
        if (empty($iterator) || !$iterator->valid()) {
            return;
        }

        $rows = [];
        $count = 0;
        $rowOffset = $offset;
        $hasRows = false;
        while ($iterator->valid()) {
            $row = $iterator->current();
            if (is_null($row)) {
                $iterator->next();
                continue;
            }
            if ($rowOffset == $this->position) {
                $rows[] = $this->cleanRow($row);
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

        $this->reader = ReaderFactory::create($this->readerType);
        try {
            $this->reader->open($this->source);
        } catch (\Box\Spout\Common\Exception\IOException $e) {
            return;
        }

        $this->reader
            // ->setTempFolder($this->config['temp_dir'])
            ->setShouldFormatDates(false);

        foreach ($this->reader->getSheetIterator() as $sheet) {
            $this->iterator = $sheet->getRowIterator();
            break;
        }
        return $this->iterator;
    }
}
