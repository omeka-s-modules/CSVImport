<?php
namespace CSVImport\Source;

interface SourceInterface
{
    /**
     * Prepare the class.
     *
     * @param array $config
     */
    public function init(array $config);

    /**
     * Get the media type managed by this class.
     *
     * @return string
     */
    public function getMediaType();

    /**
     * Set the source, that is a temp file.
     *
     * @param string $source
     */
    public function setSource($source);

    /**
     * Set the parameters.
     *
     * @param array $params
     */
    public function setParameters(array $params);

    /**
     * Get the parameters.
     *
     * @return array
     */
    public function getParameters();

    /**
     * Check if the source and parameters are valid and readable (utf-8, etc.).
     *
     * @return bool
     */
    public function isValid();

    /**
     * Return the number of rows, headers and empty rows included.
     *
     * @return int
     */
    public function countRows();

    /**
     * Get the first row of the spreadsheet.
     *
     * Note: last empty headers must not be returned, as they are not headers.
     * So it is not the same than getRow(0).
     *
     * @return array|null
     */
    public function getHeaders();

    /**
     * Get a batch of rows.
     *
     * @param int $offset
     * @param int $count
     * @return array|null
     */
    public function getRows($offset = 0, $count = -1);

    /**
     * Clean the source if needed (delete this temporary file, etc.).
     *
     * @return bool
     */
    public function clean();
}
