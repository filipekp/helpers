<?php
  
  namespace PF\helpers;
  
  /**
   * Třída Stopwatch.
   *
   * @author    Pavel Filípek <pavel@filipek-czech.cz>
   * @copyright © 2019, Proclient s.r.o.
   * @created   08.02.2019
   */
  class Stopwatch
  {
    const STOPWATCH_GLOBAL_NAME = 'global';
    
    private static $timers = [];
    private static $instance = 0;
    private $id;
    private $timer;
    
    public function __construct($alias = NULL) {
      $this->id = ((!is_null($alias)) ? $alias : self::$instance++);
      $this->timer = microtime(TRUE);
    }
    
    /**
     * Vrátí čas od spuštění.
     *
     * @return float
     */
    public function watchSplit() {
      return microtime(TRUE) - $this->timer;
    }
    
    /**
     * Zastaví stopky a vrátí čas.
     */
    public function stop() {
      $time = $this->watchSplit();
      unset(self::$timers[$this->id]);
      
      return $time;
    }
    
    public function getId() {
      return $this->id;
    }
    
    /**
     * @return Stopwatch
     */
    public static function start($alias = NULL) {
      self::$timers[$alias] = new self($alias);
      
      return self::$timers[$alias];
    }
    
    /**
     * Vrátí čas od spuštění.
     *
     * @param     $alias
     * @param int $round
     *
     * @return float
     */
    public static function getTime($alias, $round = 999) {
      $timer = MyArray::init(self::$timers)->item($alias, FALSE);
      
      return (($timer) ? round($timer->watchSplit(), $round) : 0);
    }
  }