<?php
  
  namespace PF\helpers;
  
  /**
   * Třída Calendar reprezentující ...
   *
   * @author    Pavel Filípek <pavel@filipek-czech.cz>
   * @copyright © 2019, Proclient s.r.o.
   * @created   07.02.2019
   */
  class Calendar
  {
    private static $holidays = [
      [1, 1, 'Den obnovy samostatného českého státu'],
      [1, 5, 'Svátek práce'],
      [8, 5, 'Den vítězství'],
      [5, 7, 'Den slovanských věrozvěstů Cyrila a Metoděje'],
      [6, 7, 'Den upálení mistra Jana Husa'],
      [28, 9, 'Den české státnosti'],
      [28, 10, 'Den vzniku samostatného československého státu'],
      [17, 11, 'Den boje za svobodu a demokracii'],
      [24, 12, 'Štědrý den'],
      [25, 12, '1. svátek vánoční']
    ];
  
    /**
     * Metoda pro nastavení svátků.
     *
     * @param array $holidays format: [[d, m, 'Popis svatku'], [d, m, 'Popis svatku 2'], ...]
     */
    public static function setHolidays($holidays) {
      self::$holidays = $holidays;
    }
  
    /**
     * Metoda pro pridani svátku.
     *
     * @param integer $day
     * @param integer $month
     * @param string $description
     */
    public static function addHoliday($day, $month, $description) {
      self::$holidays = array_merge(self::$holidays, [
        [$day, $month, $description]
      ]);
    }
    
    /**
     * Zjistí zda předané datum je volný den nebo ne.<br />
     * Pro nastavení svátků použijte metodu
     *
     * @param string $date
     *
     * @return bool
     */
    public static function isHoliday($date) {
      $time = strtotime(date('Y-m-d', ((is_int($date)) ? $date : strtotime($date))));
    
      $isWeekend = date('N', $time) >= 6;
      $easter = easter_date(date('Y', $time));
      $isEaster = ($time == (strtotime('+1day', $easter)) ||  // pricten jeden den, aby se pocitalo pondeli velikonocni
        $time == strtotime('-2day', $easter));      // odecteny dva dny, aby se počítal velký pátek (nový svátek od 2016)
    
      $isHoliday = FALSE;
      $holidays = (array)array_merge([], self::$holidays);
    
      foreach ($holidays as $holiday) {
        if ($holiday[0] . '.' . $holiday[1] . '.' == date('j.n.', $time)) {
          $isHoliday = TRUE;
          break;
        }
      }
    
      return ($isHoliday || $isWeekend || $isEaster);
    }
  
    /**
     * Prida/odebere dny data.
     *
     * @param string $date
     * @param int    $numOfDays (+1 or -2)
     *
     * @return int  vrací časové číslo co funkce strtotime()
     */
    public static function addBusinessDays($date, $numOfDays) {
      $current = 0;
      $direction = (($numOfDays < 0) ? '-' : '+');
      while ($current < abs($numOfDays)) {
        $date = date('Y-m-d', strtotime($direction . '1days', strtotime($date)));
      
        while (self::isHoliday($date) === TRUE) {
          $date = date('Y-m-d', strtotime($direction . '1days', strtotime($date)));
        }
      
        $current++;
      }
    
      return strtotime($date);
    }
  
    /**
     * Zjisti nasleduji pracovni den
     *
     * @param string $time
     *
     * @return false|int
     */
    public static function nextBusinessDay($time) {
      $current = strtotime('+1 day', $time);
    
      while (self::isHoliday($current)) {
        $current = strtotime('+1 day', $current);
      }
    
      return $current;
    }
  
    /**
     * Převede sekundy do formátu času<br />
     * Pokud je počet sekund menší jak 3600 tak je vrácen fromát i:s jinak H:i:s
     *
     * @param int $seconds
     *
     * @return string
     */
    public static function secondsToTimeFormat($seconds) {
      return gmdate((($seconds >= 3600) ? 'H \h ' : '') . (($seconds >= 60) ? 'i \m ' : '') . 's \s', $seconds);
    }
  
    /**
     * Funkce převede čas na textové vyjádření.
     *
     * @param int    $time
     * @param array  $translation
     * @param string $delimiter
     *
     * @return string
     */
    public static function stringTime($time, $translation = NULL, $delimiter = '&nbsp;'){
      $resultText = '';
      $translation = ((is_null($translation)) ? [
        'before_moment' => 'před chvílí',
        'before'        => 'před ',
        'minutes'       => [
          'decimal' => 'minutami',
          'one'     => 'minutou',
          'twofour' => 'minutami',
          'other'   => 'minutami',
        ],
        'hours'         => [
          'decimal' => 'hodinami',
          'one'     => 'hodinou',
          'twofour' => 'hodinami',
          'other'   => 'hodinami',
        ],
        'days'          => [
          'decimal' => 'dny',
          'one'     => 'dnem',
          'twofour' => 'dny',
          'other'   => 'dny',
        ],
      ] : $translation);
    
      $time = time() - $time;
      $pred = "před ";
    
      // mene nez 1 minuta
      if ($time >= 1) {
        $resultText = "před chvílí";
      }
    
      // vice nez 1 minuta (do 1 hodiny)
      if ($time > 59) {
        $minut      = (int)($time / 60);
        $resultText = $pred . self::inflection($minut, self::item($translation, [
            'minutes',
            'other'
          ]), self::item($translation, ['minutes', 'one']), self::item($translation, [
            'minutes',
            'twofour'
          ]), self::item($translation, ['minutes', 'decimal']), $delimiter);
      }
    
      // vice nez 1 hodina (do 1 dne)
      if (!isset($minut)) {
        $minut = 0;
      }
      if ($minut > 59) {
        $hour       = (int)($minut / 60);
        $resultText = $pred . self::inflection($hour, self::item($translation, [
            'hours',
            'other'
          ]), self::item($translation, ['hours', 'one']), self::item($translation, [
            'hours',
            'twofour'
          ]), self::item($translation, ['hours', 'decimal']), $delimiter);
      }
    
      // vice nez 1 den
      if (!isset($hour)) {
        $hour = 0;
      }
      if ($hour > 23) {
        $day        = (int)($hour / 24);
        $resultText = $pred . self::inflection($day, self::item($translation, [
            'days',
            'other'
          ]), self::item($translation, ['days', 'one']), self::item($translation, [
            'days',
            'twofour'
          ]), self::item($translation, ['days', 'decimal']), $delimiter);
      }
    
      return $resultText;
    }
  }