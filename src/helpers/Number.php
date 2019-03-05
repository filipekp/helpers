<?php
  
  namespace PF\helpers;
  
  /**
   * Třída Number.
   *
   * @author    Pavel Filípek <pavel@filipek-czech.cz>
   * @copyright © 2019, Proclient s.r.o.
   * @created   07.02.2019
   */
  class Number
  {
    /**
     * Zjisti slevu na 2 desetinna mista z puvodni ceny.
     *
     * @param float $base  puvodni cena
     * @param float $price aktualni cena
     *
     * @return float
     */
    public static function calculateDiscount($base, $price) {
      return (($base) ? round(100 - (($price * 100) / $base), 2) : 0);
    }
    
    /**
     * Naformátuje předané číslo do formátu měny.
     *
     * @param float  $number
     * @param string $prefix
     * @param string $suffix
     * @param int    $decimals
     * @param string $decimalDelimiter
     * @param string $thousandDelimiter
     *
     * @return string
     */
    public static function priceFormat($number, $prefix, $suffix, $decimals = 0, $decimalDelimiter = ',', $thousandDelimiter = ' ') {
      return (($prefix) ? $prefix : '') . number_format(round($number, $decimals), $decimals, $decimalDelimiter, $thousandDelimiter) . (($decimals == 0) ? ',-' : '') . (($suffix) ? $suffix : '');
    }
    
    /**
     * Recalculates numbers into the percents, to create one hundred unit in sum.<br />
     * Preserves associative array keys.
     *
     * @param       $data
     * @param float $total [optional=100] the number to which should be recalculated (default value is used for percents)
     * @param int   $round [optional=FALSE]
     *
     * @return array
     */
    /**
     * @param      $data
     * @param int  $total
     * @param bool $round
     *
     * @return array
     */
    public static function recalculateByRatio($data, $total = 100.0, $round = FALSE) {
      $sum = array_sum($data);
      
      if ($sum) {
        $fraction    = $total / $sum;
        $returnArray = array_map(function ($item) use ($fraction, $round) {
          $item = $fraction * $item;
          
          return (($round === FALSE) ? $item : round($item, $round));
        }, $data);
        
        // adjustment of the output field to the sum of the values ​​was 100
        $sumResult  = array_sum($returnArray);
        $difference = abs($total - $sumResult);
        if ($sumResult != $total) {
          if ($sumResult > $total) {
            $maxKey                  = array_keys($returnArray, max($returnArray));
            $returnArray[$maxKey[0]] = $returnArray[$maxKey[0]] - $difference;
          } else {
            if ($sumResult < $total) {
              $minKey                  = array_keys($returnArray, min($returnArray));
              $returnArray[$minKey[0]] = $returnArray[$minKey[0]] + $difference;
            }
          }
        }
        
        return $returnArray;
      } else {
        return $data;
      }
    }
  }