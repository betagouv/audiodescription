<?php

namespace App\Parser;

/**
 * Class to parse a CSV file.
 */
class CsvParser
{
  /**
   * Parse CSV file.
   *
   * @param string $filename
   *   The path to the CSV file.
   *
   * @return array
   *   An array of CSV data.
   */
    /**
     * @return array<array<string, string>>
     */
    public function parseCsv(string $filename, string $separator = ','): array
    {
        $rows = [];

        $handle = fopen($filename, 'r');

        if ($handle !== false) {
          // Get the headers from the first row.
            $headers = fgetcsv($handle, separator: $separator);

            if ($headers === false) {
                fclose($handle);
                return $rows;
            }

          // Process the remaining rows.
            while (($data = fgetcsv($handle, separator: $separator)) !== false) {
                // Combine the headers with the data to create an associative array.
                $rows[] = array_combine($headers, $data);
            }
            fclose($handle);
        }

        return $rows;
    }
}
