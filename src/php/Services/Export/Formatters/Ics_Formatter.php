<?php

namespace Racketmanager\Services\Export\Formatters;

use function mysql2date;

class Ics_Formatter implements Export_Formatter_Interface {
    /**
     * Formats the given data for export.
     *
     * Expects an array of objects or arrays with:
     *  - id
     *  - date (Y-m-d H:i:s)
     *  - title
     *  - location
     *
     * @param array $data    The data to format.
     * @param array $options Additional options for formatting.
     * @return string The formatted content.
     */
    public function format( array $data, array $options = array() ): string {
        $date_format = 'Ymd\THis';

        $contents  = "BEGIN:VCALENDAR\n";
        $contents .= "VERSION:2.0\n";
        $contents .= "PRODID:-//TENNIS CALENDAR//NONSGML Events //EN\n";
        $contents .= "CALSCALE:GREGORIAN\n";
        $contents .= 'DTSTAMP:' . gmdate( $date_format ) . "\n";

        foreach ( $data as $event ) {
            $event = (object) $event;

            // Handle different property names (title vs summary, etc.)
            $uid      = property_exists( $event, 'id' ) ? $event->id : '';
            $date     = property_exists( $event, 'date' ) ? $event->date : '';
            if ( property_exists( $event, 'title' ) ) {
                $summary = $event->title;
            } elseif ( property_exists( $event, 'summary' ) ) {
                $summary = $event->summary;
            } else {
                $summary = '';
            }
            $location = property_exists( $event, 'location' ) ? $event->location : '';

            $contents .= "BEGIN:VEVENT\n";
            $contents .= 'UID:' . $uid . "\n";
            $contents .= 'DTSTAMP:' . mysql2date( $date_format, $date ) . "\n";
            $contents .= 'DTSTART:' . mysql2date( $date_format, $date ) . "\n";
            $contents .= 'DTEND:' . gmdate( $date_format, strtotime( '+2 hours', strtotime( $date ) ) ) . "\n";
            $contents .= 'SUMMARY:' . $summary . "\n";
            $contents .= 'LOCATION:' . $location . "\n";
            $contents .= "END:VEVENT\n";
        }

        $contents .= 'END:VCALENDAR';

        return $contents;
    }

    /**
     * Gets the content type for the export format.
     *
     * @return string
     */
    public function get_content_type(): string {
        return self::CONTENT_TYPE_ICS;
    }

    /**
     * Gets the file extension for the export format.
     *
     * @return string
     */
    public function get_file_extension(): string {
        return 'ics';
    }
}
