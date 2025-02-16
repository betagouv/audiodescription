<?php

namespace App\Parser;

/**
 * Class to parse a CSV file.
 */
class CsvParser {

  /**
   * Parse CSV file.
   *
   * @param string $filename
   *   The path to the CSV file.
   *
   * @return array
   *   An array of CSV data.
   */
  public function parseCsv($filename, $separator) {
    $rows = [];

    $handle = fopen($filename, 'r');

    if ($handle !== FALSE) {
      // Get the headers from the first row.
      $headers = fgetcsv($handle, separator: $separator);

      // Process the remaining rows.
      while (($data = fgetcsv($handle, separator: $separator)) !== FALSE) {
        // Combine the headers with the data to create an associative array.
        $rows[] = array_combine($headers, $data);
      }
      fclose($handle);
    }

    return $rows;
  }

}
