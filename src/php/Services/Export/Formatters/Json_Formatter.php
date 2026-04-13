<?php

namespace Racketmanager\Services\Export\Formatters;

class Json_Formatter implements Export_Formatter_Interface {
    /**
     * Formats the given data for export.
     *
     * @param array $data    The data to format.
     * @param array $options Additional options for formatting.
     * @return string The formatted content.
     */
    public function format( array $data, array $options = array() ): string {
        return (string) \wp_json_encode( $data );
    }

    /**
     * Gets the content type for the export format.
     *
     * @return string
     */
    public function get_content_type(): string {
        return 'application/json';
    }

    /**
     * Gets the file extension for the export format.
     *
     * @return string
     */
    public function get_file_extension(): string {
        return 'json';
    }
}
