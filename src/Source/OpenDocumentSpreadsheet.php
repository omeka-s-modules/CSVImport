<?php
namespace CSVImport\Source;

use Box\Spout\Common\Type;

class OpenDocumentSpreadsheet extends AbstractSpreadsheet
{
    protected $mediaType = 'application/vnd.oasis.opendocument.spreadsheet';
    protected $readerType = Type::ODS;

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
        foreach ($iterator as $row) {
            if ($row && count($row) !== $number) {
                // When old columns are removed on the right, the flag may not
                // have been reset, so the default number of columns is always
                // the same, even if all columns are empty on the right. So,
                // check if all the columns on the right are empty.
                $rightColumns = array_slice($row, $number, null, true);
                if (array_filter(array_map('trim', $rightColumns), 'strlen')) {
                    $result = false;
                    break;
                }
            }
        }

        $this->reset();
        return $result;
    }
}
