<?php

namespace Drupal\audiodescription\Parser;

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
  public function parseCSV($filename) {
    $rows = [];

    if (($handle = fopen($filename, 'r')) !== FALSE) {
      // Get the headers from the first row.
      $headers = fgetcsv($handle, separator: ',');

      // Process the remaining rows.
      while (($data = fgetcsv($handle, separator: ',')) !== FALSE) {
        // Combine the headers with the data to create an associative array.
        $rows[] = array_combine($headers, $data);
      }
      fclose($handle);
    }

    return $rows;
  }
}
