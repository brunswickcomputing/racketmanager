<?php

namespace Racketmanager\Services\Export\Formatters;

class Csv_Formatter implements Export_Formatter_Interface {
    /**
     * Formats the given data for export.
     *
     * @param array $data    The data to format.
     * @param array $options Additional options for formatting.
     * @return string The formatted content.
     */
    public function format( array $data, array $options = array() ): string {
        $output = fopen( 'php://temp', 'r+' );

        // Check for headers in options.
        if ( ! empty( $options['headers'] ) ) {
            fputcsv( $output, (array) $options['headers'], ',', '"', "", "\r\n" );
        }

        foreach ( $data as $row ) {
            fputcsv( $output, (array) $row, ',', '"', "", "\r\n" );
        }

        rewind( $output );
        $content = stream_get_contents( $output );
        fclose( $output );

        return (string) $content;
    }

    /**
     * Gets the content type for the export format.
     *
     * @return string
     */
    public function get_content_type(): string {
        return self::CONTENT_TYPE_CSV;
    }

    /**
     * Gets the file extension for the export format.
     *
     * @return string
     */
    public function get_file_extension(): string {
        return 'csv';
    }
}
