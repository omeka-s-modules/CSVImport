<?php
namespace CSVImport\Source;

use Box\Spout\Common\Type;

class OpenDocumentSpreadsheet extends AbstractSpreadsheet
{
    protected $mediaType = 'application/vnd.oasis.opendocument.spreadsheet';
    protected $readerType = Type::ODS;
}
