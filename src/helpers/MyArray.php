<?php
  
  namespace PF\helpers;
  
  /**
   * Třída MyArray pro operace s polem.
   *
   * @author    Pavel Filípek <pavel@filipek-czech.cz>
   * @copyright © 2019, Proclient s.r.o.
   * @created   07.02.2019
   */
  class MyArray
  {
    protected $array = [];
    
    /**
     * MyArray constructor.
     *
     * @param $array
     */
    public function __construct(&$array) {
      $this->array = &$array;
      $this->array = (array)$this->array;
    }
    
    public static function init(&$array) {
      return new self($array);
    }
    
    /**
     * Vrací první element z pole.
     *
     * @param bool  $getKey
     * @param mixed $default vychozi navratova hodnota pokud je pole prazdne
     *
     * @return mixed|null
     */
    public function first($getKey = FALSE, $default = NULL) {
      $value = reset($this->array);
      
      return (($getKey) ? key($this->array) : (($value) ? $value : $default));
    }
    
    /**
     * Vrátí poslední prvek pole.
     *
     * @param array $array
     * @param bool  $getKey
     * @param null  $default
     *
     * @return mixed
     */
    public function last($getKey = FALSE, $default = NULL) {
      $value = end($this->array);
      return (($getKey) ? key($this->array) : (($value === FALSE) ? $default : $value));
    }
    
    /**
     * Otrimuje všechny prvky v poli.
     *
     * @return array
     */
    public function trim() {
      array_walk_recursive($this->array, function (&$item) {
        if (is_string($item)) {
          $item = trim($item);
        }
      });
      
      return $this->array;
    }
    
    /**
     * Remove or add items to array.
     *
     * @param array $remove pole klicu
     * @param array $add    asociativni pole
     *
     * @return array
     */
    public function modifyByKey(array $remove = [], array $add = []) {
      $removed     = array_diff_key($this->array, array_flip($remove));
      $this->array = $removed + $add;
      
      return $this->array;
    }
    
    /**
     * Vrati nahodnou podmnozinu podmnoziny pole.
     *
     * @param array $array  kompletni pole
     * @param int   $size   velikost pozadovane podmnoziny
     * @param int   $offset parametr pro vytvoreni subpole kompletniho pole, ze ktereho se bude vybirat nahodna podmnozina - pocatecni index
     * @param int   $length parametr pro vytvoreni subpole kompletniho pole, ze ktereho se bude vybirat nahodna podmnozina - pocet prvku od pocatecniho indexu
     *
     * @return array
     */
    public function randomArrayAssoc($size = NULL, $offset = 0, $length = NULL) {
      $keys = array_slice(array_keys($this->array), $offset, $length);
      shuffle($keys);
      
      $sample = array_slice($keys, 0, (($size) ? $size : count($keys)));
      
      return array_combine($sample, array_map(function ($key) {
        return $this->array[$key];
      }, $sample));
    }
    
    /**
     * Zjisti, zda pozadovany offset existuje v danem objektu.
     *
     * @param mixed $offset
     * @param mixed $object
     *
     * @return bool
     */
    private function offsetExists($offset, $object) {
      return ((is_a($object, '\ArrayAccess')) ? $object->offsetExists($offset) : ((is_array($object)) ? array_key_exists($offset, $object) : FALSE));
    }
    
    /**
     * "Bezpecne" odebere prvek z pole -> zkontroluje existenci.
     *
     * @param string|array $path
     * @param mixed        $default
     *
     * @return mixed
     */
    public function unsetItem($path, $default = NULL) {
      $current = &$this->array;
      $lastIdx = count((array)$path) - 1;
      
      foreach ((array)$path as $idx => $key) {
        if ($this->offsetExists($key, $current)) {
          if (is_array($current[$key]) && $idx < $lastIdx) {
            $current = &$current[$key];
          } else {
            $value = ((is_object($current[$key])) ? clone $current[$key] : $current[$key]);
            unset($current[$key]);
            
            return $value;
          }
        } else {
          break;
        }
      }
      
      
      return $default;
    }
    
    /**
     * "Bezpecne" ziska prvek z pole (celou cestu) -> zkontroluje existenci.
     *
     * @param string|array $path
     * @param mixed        $default
     *
     * @return mixed
     */
    public function item($path, $default = NULL) {
      $current = $this->array;
      foreach ((array)$path as $key) {
        if ($this->offsetExists($key, $current)) {
          $current = $current[$key];
        } else {
          return $default;
        }
      }
      
      return $current;
    }
    
    /**
     * Překonvertuje pole do CSV a stáhne.
     *
     * @param string $filename
     * @param string $delimiter
     */
    public function toCSVDownload($filename = 'export.csv', $delimiter = ';') {
      header('Content-Type: application/csv');
      header('Content-Disposition: attachment; filename="' . $filename . '";');
      
      $content = Csv::fromArray($this->array, $delimiter);
      
      echo $content;
      exit();
    }
    
    /**
     * Filtruje pole podle klicu; tzn. stejne jako funkce array_filter, ale parametr $item v callbacku je klicem.
     *
     * @param array    $array
     * @param callable $callback
     *
     * @return array
     */
    public function filterByKey(callable $callback) {
      return array_intersect_key($this->array,
        array_flip(
          array_filter(array_keys($this->array), $callback)
        )
      );
    }
    
    /**
     * Vyfiltruje pole podle prefixu klíčů.
     *
     * @param array  $array
     * @param string $prefix
     *
     * @return array
     */
    public function filterByPrefix($prefix) {
      if ($prefix) {
        $filtered = $this->filterByKey(function($key) use ($prefix) {
          return strpos($key, $prefix) === 0;
        });
      } else {
        $filtered = $this->array;
      }
      
      return $filtered;
    }
    
    /**
     * Funkce pro rekuzivni implode
     *
     * @param string $glue
     *
     * @return string
     */
    public function implodeRecursive($glue) {
      return General::implodeRecursive($glue, $this->array);
    }
    
    /**
     * Modifikace metody implode, kdy posledni prvek mnoziny je oddelen jinym oddelovacem - typicky spojkou a.
     * Polozka jedna, polozka 2 a polozka 3
     *
     * @param mixed $glue
     * @param mixed $lastSeparator
     *
     * @return string
     */
    public function implodeSeparateLast($glue = ', ', $lastSeparator = ' a ') {
      return ((count($this->array) > 1) ?
        implode($glue, array_slice($this->array, 0, -1)) . $lastSeparator . $this->last() :
        implode($glue, $this->array)
      );
    }
    
    /**
     * Seradi pole s UTF8 znaky.
     *
     * @param array    $array
     * @param bool     $desc
     * @param callable $compareCallback
     */
    public function sortAlphabet($desc = FALSE, $compareCallback = NULL) {
      $locale='cs_CZ.utf8';
      $oldLocale = setlocale(LC_COLLATE, '0');
      
      setlocale(LC_COLLATE, $locale);
      uasort($this->array, function ($a, $b) use ($desc, $compareCallback) {
        $valueA = ((is_null($compareCallback)) ? $a : $compareCallback($a));
        $valueB = ((is_null($compareCallback)) ? $b : $compareCallback($b));
        
        return (($desc) ? strcoll($valueB, $valueA) : strcoll($valueA, $valueB));
      });
      setlocale(LC_COLLATE, $oldLocale);
    }
    
    /**
     * Rekurzivne prohleda prvek a najde pozadovanou hodnotu.
     *
     * @param mixed $needle
     * @param mixed $haystack
     * @param array $callbacks pole obsahujici callback pro ziskani hodnoty (identita jako vychozi) a callback pro ziskani potomku (klic 'children' jako vychozi)
     *
     * @return mixed vrati nalezeny prvek, v pripade, ze prvek nenalezne, vrati FALSE
     */
    public static function searchRecursive($needle, $haystack, array $callbacks = ['value' => NULL, 'children' => NULL]) {
      $identity = function($item) { return $item; };
      $children = function($item) { return $item['children']; };
      
      $callbacks['value'] = ((is_null($callbacks['value'])) ? $identity : $callbacks['value']);
      $callbacks['children'] = ((is_null($callbacks['children'])) ? $children : $callbacks['children']);
      
      foreach($haystack as $item) {
        if ($callbacks['value']($item) === $needle) {
          return $item;
        } elseif (($recursive = self::searchRecursive($needle, $callbacks['children']($item), $callbacks)) !== FALSE) {
          return $recursive;
        }
      }
      
      return FALSE;
    }
  }