<?php

namespace App\Helper;

class DateHelper
{
    /**
     * Validates a date against a specified format and checks if it is not before a minimum date.
     *
     * @param string $date
     * @param string $format
     * @return bool
     */
    public static function isValidDate($date, $format = 'Y-m-d')
    {
        // Check if the date is empty
        if (empty($date)) {
            return false;
        }

        // Check if the date is invalid or does not match the format
        $d = \DateTime::createFromFormat($format, $date);
        if (!$d || $d->format($format) !== $date) {
            return false;
        }

        // Check if the date is before the minimum allowed date
        $minDate = new \DateTime('2023-01-01');
        if ($d < $minDate) {
            return false;
        }

        return true;
    }
}
