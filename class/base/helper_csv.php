<?php

/**
 * Helper class for working with CSV files
 *
 * @package Train-Up!
 */

namespace TU;

class Csv_helper {

  public static function serve_download($filename, $data = array()) {
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename={$filename}.csv");

    $output = fopen('php://output', 'w');

    foreach ($data as $row) {
      fputcsv($output, $row);
    }

    fclose($output);
    exit;
  }

}


