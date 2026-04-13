<?php

namespace Racketmanager\Services\Export\Formatters;

interface Export_Formatter_Interface {
    /**
     * Formats the given data for export.
     *
     * @param array $data    The data to format.
     * @param array $options Additional options for formatting.
     * @return string The formatted content.
     */
    public function format( array $data, array $options = array() ): string;

    /**
     * Gets the content type for the export format.
     *
     * @return string
     */
    public function get_content_type(): string;

    /**
     * Gets the file extension for the export format.
     *
     * @return string
     */
    public function get_file_extension(): string;
}
