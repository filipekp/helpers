<?php
  
  namespace PF\helpers;
  
  /**
   * Třída Csv.
   *
   * @author    Pavel Filípek <pavel@filipek-czech.cz>
   * @copyright © 2019, Proclient s.r.o.
   * @created   07.02.2019
   */
  class Csv
  {
    /**
     * Převod CSV do pole.
     *
     * @param string $filename
     * @param string $delimiter
     *
     * @return array|bool
     */
    public static function toArray($filename = '', $delimiter = ',') {
      if (!file_exists($filename) || !is_readable($filename)) {
        return FALSE;
      }
      
      $header = NULL;
      $data   = [];
      if (($handle = fopen($filename, 'r')) !== FALSE) {
        while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
          if (!$header) {
            $header = array_map(function ($column) {
              return str_replace(["\xEF\xBB\xBF"], '', $column);
            }, $row);
          } else {
            if (count($header) == count($row)) {
              $data[] = array_combine($header, $row);
            }
          }
        }
        fclose($handle);
      }
      
      return $data;
    }
    
    /**
     * Překonvertuje pole do CSV a stáhne.
     *
     * @param array  $array
     * @param string $delimiter
     *
     * @return bool|string
     */
    public static function fromArray($array, $delimiter = ';') {
      $f = fopen('php://memory', 'rw');
      fputs($f, "\xEF\xBB\xBF");
      
      foreach ($array as $line) {
        fputcsv($f, $line, $delimiter);
      }
      
      $fstat = fstat($f);
      
      fseek($f, 0);
      
      return fread($f, $fstat['size']);
    }
  }